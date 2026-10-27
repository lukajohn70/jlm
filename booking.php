<?php
// Set timeout to prevent hanging
set_time_limit(30); // 30 seconds total timeout

// Start session for CSRF token
session_start();

// Error reporting for development only
if (($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/email_utils.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = false;
$errors = [];
$formData = [];
$allowedServiceKeys = [
    'video-production',
    'music-production',
    'livestreaming',
    'motion-graphics',
    'branding',
    'web-design',
    'social-media',
    'photography',
    'consultation'
];
$allowedConsultationMethods = ['video-call', 'phone-call', 'in-person', 'email'];
$allowedBudgetRanges = ['under-1000', '1000-5000', '5000-10000', '10000-25000', 'over-25000', 'discuss'];
$allowedTimelines = ['asap', '1-2-weeks', '1-month', '2-3-months', '3-6-months', 'flexible'];
$allowedHearAboutOptions = ['google', 'social-media', 'referral', 'portfolio', 'advertisement', 'event', 'other'];

// Check if form was successfully submitted (for clearing)
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
    // Clear form data
    $formData = [];
} else {
    // Get form data for repopulation (if there were errors)
    $formData = $_POST;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting check
    $rate_limit = check_rate_limit('booking_form', 5, 300); // 5 attempts per 5 minutes
    if (!$rate_limit['allowed']) {
        $wait_time = ceil($rate_limit['wait_time'] / 60);
        $errors[] = "Too many attempts. Please wait {$wait_time} minutes before trying again.";
    } else {
        // Validate CSRF token
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Security validation failed. Please try again.';
        } else {
        $botCheck = detect_bot_form_submission($_POST, 'website', 3, 86400);
        if ($botCheck['is_bot']) {
            log_security_event('bot_submission_blocked', 'Blocked booking form bot submission', [
                'reason' => $botCheck['reason'],
                'email' => $_POST['email'] ?? '',
                'path' => $_SERVER['REQUEST_URI'] ?? 'booking.php'
            ]);

            // Return success-like response so bots cannot infer validation rules.
            $_SESSION['booking_success'] = true;
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
            exit;
        }

        $required = ['firstName', 'lastName', 'email', 'services', 'projectDescription', 'consultationDate', 'consultationTime', 'consultationMethod'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        if (!validate_email($_POST['email'] ?? '')) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!isset($_POST['services']) || !is_array($_POST['services'])) {
            $errors[] = 'Please select at least one valid service.';
        } else {
            $invalidServices = array_diff($_POST['services'], $allowedServiceKeys);
            if (!empty($invalidServices)) {
                $errors[] = 'Invalid service selection detected.';
            }
        }

        if (!in_array($_POST['consultationMethod'] ?? '', $allowedConsultationMethods, true)) {
            $errors[] = 'Invalid consultation method selected.';
        }

        if (!empty($_POST['budgetRange']) && !in_array($_POST['budgetRange'], $allowedBudgetRanges, true)) {
            $errors[] = 'Invalid budget range selected.';
        }

        if (!empty($_POST['timeline']) && !in_array($_POST['timeline'], $allowedTimelines, true)) {
            $errors[] = 'Invalid timeline selected.';
        }

        if (!empty($_POST['hearAbout']) && !in_array($_POST['hearAbout'], $allowedHearAboutOptions, true)) {
            $errors[] = 'Invalid referral source selected.';
        }
        
        if (empty($errors)) {
            try {
                // Generate ticket number
                $ticketNumber = generate_ticket_number('BK');
                
                // Save to database (only if database connection is available)
                if ($pdo) {
                    $fullName = $_POST['firstName'] . ' ' . $_POST['lastName'];
                    $consultationDateTime = $_POST['consultationDate'] . ' ' . $_POST['consultationTime'];
                    
                    $stmt = $pdo->prepare("INSERT INTO booking_submissions (ticket_number, name, email, phone, service, project_description, datetime, consultation_method, budget, timeline, hear_about, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->execute([
                        $ticketNumber,
                        sanitize_input($fullName),
                        sanitize_input($_POST['email']),
                        sanitize_input($_POST['phone'] ?? ''),
                        implode(', ', $_POST['services']),
                        sanitize_input($_POST['projectDescription']),
                        $consultationDateTime,
                        sanitize_input($_POST['consultationMethod']),
                        sanitize_input($_POST['budgetRange'] ?? ''),
                        sanitize_input($_POST['timeline'] ?? ''),
                        sanitize_input($_POST['hearAbout'] ?? '')
                    ]);
                    
                    // Get the inserted ID
                    $bookingId = $pdo->lastInsertId();
                    
                    // Create ticket in tracking system (using new system from functions.php)
                    try {
                        create_ticket_tracking($ticketNumber, 'booking', $bookingId);
                    } catch (Exception $e) {
                        // Log the error but don't crash the form submission
                        error_log("Ticket tracking creation failed: " . $e->getMessage());
                    }
                    
                    error_log("BOOKING FORM DEBUG - Ticket number: " . $ticketNumber);
                } else {
                    // In development, just log the submission
                    error_log("Booking submission (development mode): " . json_encode($_POST));
                }
                
                // Send email notifications (with error handling and timeout)
                try {
                    // Set a timeout for email operations
                    set_time_limit(15); // 15 seconds timeout
                    
                    $emailResult = handleBookingFormEmail(
                        $_POST['firstName'],
                        $_POST['lastName'],
                        $_POST['email'],
                        $_POST['phone'] ?? '',
                        $_POST['services'],
                        $_POST['projectDescription'],
                        $consultationDateTime,
                        $_POST['consultationMethod'],
                        $_POST['budgetRange'] ?? '',
                        $_POST['timeline'] ?? '',
                        $ticketNumber
                    );
                    
                    // Log email results for debugging
                    if (!$emailResult['admin'] || !$emailResult['user']) {
                        error_log("Booking email notification failed - Admin: " . ($emailResult['admin'] ? 'success' : 'failed') . ", User: " . ($emailResult['user'] ? 'success' : 'failed'));
                    }
                } catch (Exception $e) {
                    // In development mode, log the error but don't crash
                    error_log("Email sending failed in development: " . $e->getMessage());
                    $emailResult = ['admin' => false, 'user' => false];
                }
                
                // Set success flag and redirect to clear form
                $_SESSION['booking_success'] = true;
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
                exit;
                
            } catch (PDOException $e) {
                if ($pdo) {
                    $errors[] = 'Sorry, there was an error saving your booking. Please try again.';
                } else {
                    // In development mode, continue without database
                    error_log("Database error in development: " . $e->getMessage());
                    // Still show modal even if database fails in development
                    $_SESSION['booking_success'] = true;
                    $success = true;
                }
            }
        }
        }
    }
}

// Define variables for form display
$services = [
    'video-production' => ['Video Production', 'Professional video content creation'],
    'music-production' => ['Music Production', 'Recording, mixing, and mastering'],
    'livestreaming' => ['Livestreaming', 'Multi-platform streaming solutions'],
    'motion-graphics' => ['Motion Graphics', 'Dynamic animations and graphics'],
    'branding' => ['Branding', 'Logo design and brand identity'],
    'web-design' => ['Web Design', 'Modern, responsive websites'],
    'social-media' => ['Social Media', 'Content creation and management'],
    'photography' => ['Photography', 'Professional photo shoots'],
    'consultation' => ['Consultation', 'Strategic planning and advice']
];

$consultationMethods = [
    'video-call' => 'Video Call (Zoom/Teams)',
    'phone-call' => 'Phone Call',
    'in-person' => 'In-Person Meeting',
    'email' => 'Email Consultation'
];

$budgetRanges = [
    'under-1000' => 'Under ₦1,000,000',
    '1000-5000' => '₦1,000,000 - ₦5,000,000',
    '5000-10000' => '₦5,000,000 - ₦10,000,000',
    '10000-25000' => '₦10,000,000 - ₦25,000,000',
    'over-25000' => 'Over ₦25,000,000',
    'discuss' => 'Let\'s discuss'
];

$timelines = [
    'asap' => 'As soon as possible',
    '1-2-weeks' => '1-2 weeks',
    '1-month' => '1 month',
    '2-3-months' => '2-3 months',
    '3-6-months' => '3-6 months',
    'flexible' => 'Flexible timeline'
];

$hearAboutOptions = [
    'google' => 'Google Search',
    'social-media' => 'Social Media',
    'referral' => 'Referral/Recommendation',
    'portfolio' => 'Saw our portfolio',
    'advertisement' => 'Advertisement',
    'event' => 'Event/Conference',
    'other' => 'Other'
];

// Set page-specific variables
$page_title = 'Book Consultation';
$page_description = 'Ready to start your creative project? Book a consultation with JLM Creative Media and let\'s bring your vision to life.';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?> - JLM Creative Media</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $page_description; ?>">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-6 right-6 z-[70] space-y-3 max-w-sm"></div>

<style>
    body { 
        background: linear-gradient(135deg, #f7f8fa 0%, #e8eaf6 100%);
        font-family: 'Inter', Arial, sans-serif;
    }
    
    .booking-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #6b7280;
        text-decoration: none;
        font-weight: 500;
        margin-bottom: 2rem;
        transition: color 0.2s;
    }
    
    .back-btn:hover {
        color: #1b2299;
    }
    
    
    
    .page-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .page-title {
        font-size: 3rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1rem;
    }
    
    .page-title .gradient {
        background: linear-gradient(135deg, #1b2299, #e4306d);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .page-subtitle {
        font-size: 1.25rem;
        color: #6b7280;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    .booking-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        padding: 2.5rem;
        margin-bottom: 2rem;
    }
    
    .form-section {
        margin-bottom: 2rem;
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .section-title svg {
        color: #1b2299;
        width: 1.5rem;
        height: 1.5rem;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    
    label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    
    .required {
        color: #dc2626;
    }
    
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="date"],
    input[type="time"],
    select,
    textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 1rem;
        transition: all 0.2s;
        background: #f9fafb;
    }
    
    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #1b2299;
        background: white;
        box-shadow: 0 0 0 3px rgba(27, 34, 153, 0.1);
    }
    
    textarea {
        min-height: 120px;
        resize: vertical;
    }
    
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 0.5rem;
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
        background: #f9fafb;
    }
    
    .checkbox-item:hover {
        border-color: #1b2299;
        background: #f0f9ff;
    }
    
    .checkbox-item input[type="checkbox"] {
        width: auto;
        margin: 0;
    }
    
    .checkbox-item input[type="checkbox"]:checked + .checkbox-content {
        color: #1b2299;
        font-weight: 500;
    }
    
    .checkbox-content {
        flex: 1;
    }
    
    .checkbox-title {
        font-weight: 500;
        color: #374151;
    }
    
    .checkbox-description {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    
    .radio-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 0.5rem;
    }
    
    .radio-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
        background: #f9fafb;
    }
    
    .radio-item:hover {
        border-color: #1b2299;
        background: #f0f9ff;
    }
    
    .radio-item input[type="radio"] {
        width: auto;
        margin: 0;
    }
    
    .radio-item input[type="radio"]:checked + .radio-content {
        color: #1b2299;
        font-weight: 500;
    }
    
    .radio-content {
        flex: 1;
        font-weight: 500;
        color: #374151;
    }
    
    .submit-section {
        text-align: center;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 2px solid #f3f4f6;
    }
    
    .submit-btn {
        background: linear-gradient(135deg, #1b2299, #e4306d);
        color: white;
        border: none;
        padding: 14px 24px;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 160px;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(27,34,153,0.08);
    }
    
    .submit-btn:hover {
        background: linear-gradient(135deg, #151d7a, #c2285a);
        box-shadow: 0 4px 16px rgba(27,34,153,0.12);
    }
    
    .submit-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    .error-message {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }
    
    .success-message {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #16a34a;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }
    
    .info-section {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .info-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .info-title svg {
        color: #1b2299;
        width: 1.5rem;
        height: 1.5rem;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    .info-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .info-icon {
        width: 3rem;
        height: 3rem;
        background: linear-gradient(135deg, #1b2299, #e4306d);
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .info-content h4 {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    
    .info-content p {
        color: #6b7280;
        font-size: 0.875rem;
        line-height: 1.5;
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }
    
    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 2rem;
        border-radius: 1rem;
        width: 90%;
        max-width: 500px;
        text-align: center;
        position: relative;
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal-icon {
        width: 4rem;
        height: 4rem;
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        color: white;
        font-size: 2rem;
    }
    
    .modal-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
    }
    
    .modal-message {
        color: #6b7280;
        margin-bottom: 2rem;
        line-height: 1.6;
    }
    
    .modal-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .modal-button {
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #1b2299, #e4306d);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(27, 34, 153, 0.3);
    }
    
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
        border: 2px solid #e5e7eb;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
        transform: translateY(-1px);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .booking-container {
            padding: 1rem;
        }
        
        .page-title {
            font-size: 2rem;
        }
        
        .booking-card {
            padding: 1.5rem;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .checkbox-group,
        .radio-group {
            grid-template-columns: 1fr;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .modal-content {
            margin: 10% auto;
            width: 95%;
            padding: 1.5rem;
        }
        
        .modal-buttons {
            flex-direction: column;
        }
    }
</style>

    <!-- Back to Home Button -->
    <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-[9999]">
        <a href="index.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 text-sm font-medium bg-white/90 backdrop-blur-sm px-4 py-3 rounded-full shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Home
        </a>
    </div>
    
    <div class="booking-container">

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Book Your <span class="gradient">Consultation</span></h1>
        <p class="page-subtitle">Ready to bring your creative vision to life? Let's discuss your project and create something amazing together.</p>
    </div>

    <!-- Information Section -->
    <div class="info-section">
        <h2 class="info-title">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            What to Expect
        </h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-icon">📞</div>
                <div class="info-content">
                    <h4>Free Consultation</h4>
                    <p>No cost, no obligation. Just a friendly chat about your project.</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">⏱️</div>
                <div class="info-content">
                    <h4>30-45 Minutes</h4>
                    <p>Enough time to discuss your needs and explore possibilities.</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">💡</div>
                <div class="info-content">
                    <h4>Expert Advice</h4>
                    <p>Get insights from our experienced creative team.</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">📋</div>
                <div class="info-content">
                    <h4>Custom Quote</h4>
                    <p>Receive a detailed proposal tailored to your project.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="booking-card">
        <form method="POST" action="" id="bookingForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="form_started_at" value="<?php echo time(); ?>">
            <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <strong>Please fix the following errors:</strong>
                    <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Personal Information -->
            <div class="form-section">
                <h3 class="section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Personal Information
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstName">First Name <span class="required">*</span></label>
                        <input type="text" id="firstName" name="firstName" required 
                               value="<?php echo htmlspecialchars($formData['firstName'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name <span class="required">*</span></label>
                        <input type="text" id="lastName" name="lastName" required 
                               value="<?php echo htmlspecialchars($formData['lastName'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Services -->
            <div class="form-section">
                <h3 class="section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Services Needed <span class="required">*</span>
                </h3>
                <div class="checkbox-group">
                    <?php foreach ($services as $key => $service): ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="services[]" value="<?php echo $key; ?>"
                                   <?php echo (isset($formData['services']) && in_array($key, $formData['services'])) ? 'checked' : ''; ?>>
                            <div class="checkbox-content">
                                <div class="checkbox-title"><?php echo $service[0]; ?></div>
                                <div class="checkbox-description"><?php echo $service[1]; ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Project Details -->
            <div class="form-section">
                <h3 class="section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Project Details <span class="required">*</span>
                </h3>
                <div class="form-group full-width">
                    <label for="projectDescription">Project Description <span class="required">*</span></label>
                    <textarea id="projectDescription" name="projectDescription" required 
                              placeholder="Tell us about your project. What are your goals? What's your vision? Any specific requirements or preferences?"><?php echo htmlspecialchars($formData['projectDescription'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Consultation Details -->
            <div class="form-section">
                <h3 class="section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Consultation Details <span class="required">*</span>
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="consultationDate">Preferred Date <span class="required">*</span></label>
                        <input type="date" id="consultationDate" name="consultationDate" required 
                               value="<?php echo htmlspecialchars($formData['consultationDate'] ?? ''); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="consultationTime">Preferred Time <span class="required">*</span></label>
                        <input type="time" id="consultationTime" name="consultationTime" required 
                               value="<?php echo htmlspecialchars($formData['consultationTime'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Consultation Method <span class="required">*</span></label>
                    <div class="radio-group">
                        <?php foreach ($consultationMethods as $key => $method): ?>
                            <label class="radio-item">
                                <input type="radio" name="consultationMethod" value="<?php echo $key; ?>" required
                                       <?php echo (isset($formData['consultationMethod']) && $formData['consultationMethod'] === $key) ? 'checked' : ''; ?>>
                                <div class="radio-content"><?php echo $method; ?></div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="form-section">
                <h3 class="section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Additional Information
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="budgetRange">Budget Range</label>
                        <select id="budgetRange" name="budgetRange">
                            <option value="">Select budget range</option>
                            <?php foreach ($budgetRanges as $key => $range): ?>
                                <option value="<?php echo $key; ?>" 
                                        <?php echo (isset($formData['budgetRange']) && $formData['budgetRange'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo $range; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="timeline">Project Timeline</label>
                        <select id="timeline" name="timeline">
                            <option value="">Select timeline</option>
                            <?php foreach ($timelines as $key => $timeline): ?>
                                <option value="<?php echo $key; ?>" 
                                        <?php echo (isset($formData['timeline']) && $formData['timeline'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo $timeline; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="hearAbout">How did you hear about us?</label>
                    <select id="hearAbout" name="hearAbout">
                        <option value="">Select option</option>
                        <?php foreach ($hearAboutOptions as $key => $option): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($formData['hearAbout']) && $formData['hearAbout'] === $key) ? 'selected' : ''; ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="submit-section">
                <button type="submit" class="submit-btn" id="submitBtn">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Book Consultation
                </button>
            </div>
        </form>
    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Toast notification system
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        
        const toast = document.createElement('div');
        const id = 'toast-' + Date.now();
        toast.id = id;
        
        const bgColor = type === 'success' ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-red-500 to-red-600';
        const borderColor = type === 'success' ? 'border-green-400' : 'border-red-400';
        const icon = type === 'success' ? 
            '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' :
            '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        
        toast.className = `${bgColor} ${borderColor} text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-4 transform translate-x-full transition-all duration-500 ease-out max-w-md border-l-4 backdrop-blur-sm`;
        toast.innerHTML = `
            <div class="flex-shrink-0">
                ${icon}
            </div>
            <div class="flex-1">
                <p class="font-semibold text-sm">${type === 'success' ? 'Success!' : 'Error'}</p>
                <p class="text-sm opacity-90">${message}</p>
            </div>
            <button onclick="closeToast('${id}')" class="flex-shrink-0 text-white hover:text-gray-200 transition-colors p-1 rounded-full hover:bg-white/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;
        
        container.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
        }, 100);
        
        // Auto remove after 6 seconds
        setTimeout(() => {
            closeToast(id);
        }, 6000);
    }

    function closeToast(id) {
        const toast = document.getElementById(id);
        if (toast) {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }
    }
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = '#dc2626';
            } else {
                field.style.borderColor = '#e5e7eb';
            }
        });
        
        // Check if at least one service is selected
        const serviceCheckboxes = form.querySelectorAll('input[name="services[]"]:checked');
        if (serviceCheckboxes.length === 0) {
            isValid = false;
            showToast('Please select at least one service.', 'error');
            e.preventDefault();
            return;
        }
        
        if (!isValid) {
            e.preventDefault();
            showToast('Please fill in all required fields.', 'error');
            return;
        }
        
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Submitting...';
    });
    
    // Show success toast if booking was successful
    <?php if ($success): ?>
    showToast('Thank you for booking your consultation. We\'ll contact you within 24 hours to confirm the details.', 'success');
    <?php endif; ?>
    
    // Show error toasts if there are errors
    <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
    showToast('<?php echo addslashes($error); ?>', 'error');
    <?php endforeach; ?>
    <?php endif; ?>
    
    // Auto-hide error messages after 10 seconds
    const errorMessage = document.querySelector('.error-message');
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.display = 'none';
        }, 10000);
    }
    
    // Real-time form validation
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.style.borderColor = '#dc2626';
            } else {
                this.style.borderColor = '#e5e7eb';
            }
        });
        
        input.addEventListener('input', function() {
            if (this.style.borderColor === 'rgb(220, 38, 38)') {
                this.style.borderColor = '#e5e7eb';
            }
        });
    });
});
</script>
<?php require_once __DIR__ . '/includes/cookie_consent.php'; ?>
</body>
</html> 
