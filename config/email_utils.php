<?php
/**
 * Email Utilities for JLM Website
 * Uses PHPMailer with external SMTP service for reliable email delivery
 */

// Include email templates
require_once __DIR__ . '/email_templates.php';

// Include functions for ticket generation
require_once __DIR__ . '/../includes/functions.php';

// Email Configuration
define('ADMIN_EMAIL', 'lukajohn70@gmail.com');
define('FROM_EMAIL', 'info@jlm.com.ng'); // Your domain email
define('FROM_NAME', 'JLM');

// SMTP Configuration - Domain Email Settings
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
    // Development email settings (log emails instead of sending)
    define('SMTP_HOST', 'localhost');
    define('SMTP_PORT', 1025);
    define('SMTP_USERNAME', 'dev@localhost');
    define('SMTP_PASSWORD', 'dev_password');
    define('SMTP_SECURE', 'none');
} else {
    // Production email settings
    define('SMTP_HOST', 'mail.jlm.com.ng'); // Your domain's mail server
    define('SMTP_PORT', 465);
    define('SMTP_USERNAME', 'info@jlm.com.ng'); // Your domain email
    define('SMTP_PASSWORD', 'C*c4V}v?pW^+N~NE'); // Your domain email password
    define('SMTP_SECURE', 'ssl');
}

/**
 * Send email using PHPMailer with SMTP
 */
function sendEmail($to, $subject, $message, $isHTML = true) {
    // In development mode, just log the email instead of sending
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        error_log("DEVELOPMENT MODE - Email would be sent to: $to");
        error_log("DEVELOPMENT MODE - Subject: $subject");
        error_log("DEVELOPMENT MODE - Message: " . substr($message, 0, 200) . "...");
        return true; // Return true to simulate successful email sending
    }
    
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer not found. Please install it via Composer or download manually.");
        return false;
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Timeout settings to prevent hanging
        $mail->Timeout = 10; // 10 seconds timeout
        $mail->SMTPKeepAlive = false; // Don't keep connection alive
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        if (!$isHTML) {
            $mail->AltBody = strip_tags($message);
        }
        
        $mail->send();
        error_log("Email sent successfully to: " . $to);
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send admin notification for form submissions
 */
function sendAdminNotification($formType, $formData) {
    $subject = "New $formType Submission - JLM Website";
    
    // Generate ticket number
    $ticketNumber = generate_ticket_number();
    
    // Create content
    $content = '
    <div style="margin-bottom: 20px;">
        <h2 style="color: #333; margin-bottom: 15px;">New ' . $formType . ' Submission</h2>
        <p style="color: #666; margin-bottom: 20px;">Received on ' . date('F j, Y \a\t g:i A') . '</p>
    </div>
    ' . getFormDataDisplay($formData) . '
    <div class="highlight">
        <p style="margin: 0;"><strong>Next Steps:</strong></p>
        <ul style="margin: 10px 0 0 0; padding-left: 20px;">
            <li>Review the submission details above</li>
            <li>Contact the client within 24 hours</li>
            <li>Update the client on project status</li>
            <li>Use the ticket number for reference</li>
        </ul>
    </div>';
    
    $message = getCompleteEmailTemplate("New $formType Submission", $content, $ticketNumber);
    
    return sendEmail(ADMIN_EMAIL, $subject, $message);
}

/**
 * Send confirmation email to user
 */
function sendConfirmationEmail($userEmail, $userName, $formType) {
    $subject = "Thank you for your $formType - JLM";
    
    // Generate ticket number
    $ticketNumber = generate_ticket_number();
    
    // Create content
    $content = '
    <div style="margin-bottom: 20px;">
        <h2 style="color: #333; margin-bottom: 15px;">Thank You!</h2>
        <p style="color: #666; margin-bottom: 20px;">Dear ' . $userName . ',</p>
        <p>Thank you for your ' . $formType . '. We have received your message and will get back to you within 24 hours.</p>
    </div>
    
    <div class="highlight">
        <p style="margin: 0;"><strong>What happens next?</strong></p>
        <ul style="margin: 10px 0 0 0; padding-left: 20px;">
            <li>Our team will review your submission</li>
            <li>We\'ll contact you within 24 hours</li>
            <li>We\'ll discuss your project requirements</li>
            <li>We\'ll provide a detailed proposal</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="https://jlm.com.ng" class="button">Visit Our Website</a>
    </div>
    
    <p style="color: #666;">Best regards,<br><strong>The JLM Team</strong></p>';
    
    $message = getCompleteEmailTemplate("Thank You!", $content, $ticketNumber);
    
    return sendEmail($userEmail, $subject, $message);
}

/**
 * Handle contact form email notifications
 */
function handleContactFormEmail($name, $email, $phone, $service, $message) {
    $formData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'service' => $service,
        'message' => $message
    ];
    
    $adminResult = sendAdminNotification('Contact Form', $formData);
    $userResult = sendConfirmationEmail($email, $name, 'contact form submission');
    
    return [
        'admin' => $adminResult,
        'user' => $userResult
    ];
}

/**
 * Handle booking form email notifications
 */
function handleBookingFormEmail($firstName, $lastName, $email, $phone, $services, $projectDescription, $consultationDateTime, $consultationMethod, $budgetRange, $timeline) {
    $formData = [
        'name' => $firstName . ' ' . $lastName,
        'email' => $email,
        'phone' => $phone,
        'services' => is_array($services) ? implode(', ', $services) : $services,
        'project_description' => $projectDescription,
        'consultation_datetime' => $consultationDateTime,
        'consultation_method' => $consultationMethod,
        'budget_range' => $budgetRange,
        'timeline' => $timeline
    ];
    
    $adminResult = sendAdminNotification('Booking Form', $formData);
    $userResult = sendConfirmationEmail($email, $firstName . ' ' . $lastName, 'booking request');
    
    return [
        'admin' => $adminResult,
        'user' => $userResult
    ];
}

/**
 * Handle registration email notifications
 */
function handleRegistrationEmail($name, $email, $role) {
    $formData = [
        'name' => $name,
        'email' => $email,
        'role' => $role
    ];
    
    $adminResult = sendAdminNotification('User Registration', $formData);
    $userResult = sendWelcomeEmail($email, $name, $role);
    
    return [
        'admin' => $adminResult,
        'user' => $userResult
    ];
}

/**
 * Send welcome email to new registered users
 */
function sendWelcomeEmail($userEmail, $userName, $role) {
    $subject = "Welcome to JLM!";
    
    // Generate ticket number
    $ticketNumber = generate_ticket_number();
    
    // Create content
    $content = '
    <div style="margin-bottom: 20px;">
        <h2 style="color: #333; margin-bottom: 15px;">Welcome to JLM!</h2>
        <p style="color: #666; margin-bottom: 20px;">Dear ' . $userName . ',</p>
        <p>Welcome to JLM! Your account has been successfully created as a <strong>' . $role . '</strong>.</p>
    </div>
    
    <div class="highlight">
        <p style="margin: 0;"><strong>What you can do now:</strong></p>
        <ul style="margin: 10px 0 0 0; padding-left: 20px;">
            <li>Log in to your account</li>
            <li>Access exclusive content</li>
            <li>Book consultations</li>
            <li>Track your projects</li>
            <li>Connect with our team</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="https://jlm.com.ng" class="button">Get Started</a>
    </div>
    
    <p style="color: #666;">If you have any questions, feel free to contact us anytime.</p>
    <p style="color: #666;">Best regards,<br><strong>The JLM Team</strong></p>';
    
    $message = getCompleteEmailTemplate("Welcome to JLM!", $content, $ticketNumber);
    
    return sendEmail($userEmail, $subject, $message);
}

/**
 * Check if we're in development mode
 */
function isDevelopmentMode() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return in_array($host, ['localhost', '127.0.0.1', '::1']) || strpos($host, '.local') !== false;
}

/**
 * Development mode email logging (for testing without sending real emails)
 */
function logEmailInDevelopment($to, $subject, $message) {
    if (isDevelopmentMode()) {
        error_log("DEVELOPMENT MODE - Email would be sent:");
        error_log("To: " . $to);
        error_log("Subject: " . $subject);
        error_log("Message: " . $message);
        error_log("--- END EMAIL ---");
        return true;
    }
    return false;
}

/**
 * Send contact form reply email
 */
function sendContactReply($userEmail, $userName, $subject, $message, $ticketNumber = null) {
    $emailSubject = "Re: " . $subject;
    
    // Create content
    $content = '
    <div style="margin-bottom: 20px;">
        <h2 style="color: #333; margin-bottom: 15px;">Response from JLM</h2>
        <p style="color: #666; margin-bottom: 20px;">Dear ' . $userName . ',</p>
        <p>Thank you for contacting JLM. Here is our response to your inquiry:</p>
    </div>';
    
    // Add ticket number if provided
    if ($ticketNumber) {
        $content .= '
        <div style="background: #e3f2fd; border: 2px solid #2196f3; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #1976d2;">Reference Ticket</h3>
            <p style="margin: 0; font-size: 18px; font-weight: bold; color: #2196f3; font-family: monospace;">' . $ticketNumber . '</p>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">Please keep this number for future reference</p>
        </div>';
    }
    
    $content .= '
    <div style="background-color: #f8f9fa; padding: 20px; border-left: 4px solid #007bff; margin: 20px 0;">
        <p style="margin: 0; color: #333; line-height: 1.6;">' . nl2br(htmlspecialchars($message)) . '</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="https://jlm.com.ng" style="display: inline-block; background: linear-gradient(135deg, #1b2299 0%, #e4306d 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: 600;">Visit Our Website</a>
    </div>
    
    <p style="color: #666;">If you have any further questions, please don\'t hesitate to contact us.</p>
    <p style="color: #666;">Best regards,<br><strong>The JLM Team</strong></p>';
    
    $emailMessage = getCompleteEmailTemplate("Response from JLM", $content, $ticketNumber);
    
    return sendEmail($userEmail, $emailSubject, $emailMessage);
}

/**
 * Send newsletter email to subscribers
 */
function sendNewsletterEmail($subscriberEmail, $firstName, $subject, $content) {
    // Personalize content
    $personalizedContent = str_replace('{{first_name}}', $firstName, $content);
    
    // Create email content
    $emailContent = '
    <div style="margin-bottom: 20px;">
        <h2 style="color: #333; margin-bottom: 15px;">' . htmlspecialchars($subject) . '</h2>
        <p style="color: #666; margin-bottom: 20px;">Dear ' . $firstName . ',</p>
    </div>
    
    <div style="color: #333; line-height: 1.6;">
        ' . $personalizedContent . '
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="https://jlm.com.ng" class="button">Visit Our Website</a>
    </div>
    
    <div style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 30px;">
        <p style="color: #666; font-size: 12px;">
            You received this email because you subscribed to our newsletter.<br>
            <a href="https://jlm.com.ng/unsubscribe?email=' . urlencode($subscriberEmail) . '" style="color: #007bff;">Unsubscribe</a>
        </p>
    </div>';
    
    $emailMessage = getCompleteEmailTemplate($subject, $emailContent);
    
    return sendEmail($subscriberEmail, $subject, $emailMessage);
}

/**
 * Send subscription confirmation email
 */
function sendSubscriptionConfirmation($email, $firstName) {
    $subject = "Welcome to JLM Newsletter!";
    
    $content = '
    <div style="margin-bottom: 20px;">
        <h2 style="color: #333; margin-bottom: 15px;">Welcome to JLM Newsletter!</h2>
        <p style="color: #666; margin-bottom: 20px;">Dear ' . $firstName . ',</p>
        <p>Thank you for subscribing to our newsletter! You\'ll now receive updates about our latest projects, industry insights, and exclusive offers.</p>
    </div>
    
    <div class="highlight">
        <p style="margin: 0;"><strong>What you\'ll receive:</strong></p>
        <ul style="margin: 10px 0 0 0; padding-left: 20px;">
            <li>Latest project updates and portfolio highlights</li>
            <li>Industry insights and creative tips</li>
            <li>Exclusive offers and promotions</li>
            <li>Behind-the-scenes content</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="https://jlm.com.ng" class="button">Explore Our Work</a>
    </div>
    
    <p style="color: #666;">Best regards,<br><strong>The JLM Team</strong></p>';
    
    $message = getCompleteEmailTemplate("Welcome to JLM Newsletter!", $content);
    
    return sendEmail($email, $subject, $message);
} 