<?php
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

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$testimonial_result = null;
$formData = [];

// Check if form was successfully submitted (for clearing)
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $testimonial_result = ['success' => true, 'message' => 'Thank you for sharing your experience! Your testimonial has been submitted and will be reviewed shortly.'];
    // Clear form data
    $formData = [];
} else {
    // Get form data for repopulation (if there were errors)
    $formData = $_POST;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testimonial_form'])) {
    // Rate limiting check
    $rate_limit = check_rate_limit('testimonial_form', 5, 300); // 5 attempts per 5 minutes
    if (!$rate_limit['allowed']) {
        $wait_time = ceil($rate_limit['wait_time'] / 60);
        $testimonial_result = ['success' => false, 'message' => "Too many attempts. Please wait {$wait_time} minutes before trying again."];
    } else {
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $testimonial_result = ['success' => false, 'message' => 'Security validation failed. Please try again.'];
        } else {
            $testimonial_result = process_testimonial_form($_POST);
                            if ($testimonial_result['success']) {
                    // Set success flag and redirect to clear form
                    $_SESSION['testimonial_success'] = true;
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
                    exit;
                }
        }
    }
}

// Set page-specific variables
$page_title = 'Share Your Experience';
$page_description = 'Share your experience with JLM. Your feedback helps us improve and inspires others to work with us.';
?>

<style>
    body { 
        background: linear-gradient(135deg, #f7f8fa 0%, #e8eaf6 100%);
        font-family: 'Inter', Arial, sans-serif;
        margin: 0;
        padding: 0;
    }
    .testimonial-container { 
        max-width: 900px; 
        margin: 0 auto; 
        position: relative;
        padding: 2rem 1rem;
    }
    .top-section { text-align: center; margin-bottom: 40px; }
    .testimonial-title { font-size: 2.5rem; font-weight: 700; color: #222; margin-bottom: 12px; line-height: 1.1; }
    .testimonial-title .gradient { background: linear-gradient(90deg, #1b2299, #e4306d); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .testimonial-subtitle { font-size: 1.25rem; color: #555; margin-bottom: 0; max-width: 700px; margin-left: auto; margin-right: auto; line-height: 1.5; }

    .gradient-title { font-size: 2.6rem; font-weight: 700; margin: 0 0 10px 0; }
    .gradient-title .gradient { background: linear-gradient(90deg, #1b2299, #e4306d); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .subtitle { font-size: 1.18rem; color: #555; margin-bottom: 36px; }
    .card { background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(27,34,153,0.07); margin-bottom: 32px; padding: 36px 32px 28px 32px; border: none; }
    .section-title { display: flex; align-items: center; font-size: 1.25rem; font-weight: 600; color: #2d2d2d; margin-bottom: 8px; gap: 10px; }
    .section-title svg { color: #5b4ee6; width: 1.5em; height: 1.5em; flex-shrink: 0; }
    .section-desc { color: #888; font-size: 1.02rem; margin-bottom: 18px; }
    .form-row { display: flex; gap: 24px; margin-bottom: 0; }
    .form-col { flex: 1; min-width: 0; }
    .form-group { margin-bottom: 20px; }
    label { display: block; margin-bottom: 6px; font-weight: 500; color: #222; }
    input[type="text"], input[type="email"], input[type="date"], input[type="time"], select, textarea {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #ececf0;
        border-radius: 8px;
        font-size: 1.05rem;
        background: #f7f7fa;
        color: #222;
        box-sizing: border-box;
    }
    input:focus, select:focus, textarea:focus { outline: 2px solid #bdb6f6; border-color: #bdb6f6; }
    textarea { min-height: 90px; }
    
    .rating-container { display: flex; gap: 8px; margin-top: 8px; }
    .rating-star { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 2px solid #e5e7eb; border-radius: 50%; cursor: pointer; transition: all 0.2s; background: white; }
    .rating-star:hover { border-color: #fbbf24; background: #fef3c7; }
    .rating-star.selected { border-color: #f59e0b; background: #fef3c7; }
    .rating-star svg { width: 20px; height: 20px; color: #d1d5db; transition: color 0.2s; }
    .rating-star.selected svg, .rating-star:hover svg, .rating-star.hover svg { color: #f59e0b; }
    .rating-star.hover { border-color: #fbbf24; background: #fef3c7; }
    
    .error { color: #c00; margin-bottom: 12px; }
    .success { color: #080; margin-bottom: 12px; font-weight: bold; font-size: 1.2rem; }
    .action-row { display: flex; gap: 18px; margin-top: 32px; margin-bottom: 48px; justify-content: center; }
    .submit-btn {
        border: none;
        outline: none;
        font-weight: 600;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(27,34,153,0.08);
        transition: background 0.2s, box-shadow 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: linear-gradient(90deg, #5b4ee6, #e4306d);
        color: #fff;
        padding: 14px 24px;
        font-size: 1rem;
        margin-bottom: 0;
        cursor: pointer;
        min-width: 160px;
    }
    .submit-btn:hover {
        background: linear-gradient(90deg, #1b2299, #e4306d);
        box-shadow: 0 4px 16px rgba(27,34,153,0.12);
    }
    
    
    
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    
    /* Info Section Styles (matching booking page) */
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
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .form-row { flex-direction: column; gap: 0; }
        .info-grid { grid-template-columns: 1fr; }
        .testimonial-title { font-size: 2rem; }
        .gradient-title { font-size: 2.2rem; }
    }
</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - JLM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="assets/images/jlm.png">
</head>
<body>
    <!-- Back to Home Button - Styled for light background -->
    <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-[9999]">
        <a href="index.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 text-sm font-medium bg-white/90 backdrop-blur-sm px-4 py-3 rounded-full shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            Back to Home
        </a>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-6 right-6 z-[70] space-y-3 max-w-sm"></div>
    
    <div class="testimonial-container">
                <!-- Top Section -->
                <div class="top-section">
                    <h1 class="testimonial-title">Share Your <span class="gradient">Experience</span></h1>
                    <p class="testimonial-subtitle">Help us improve and inspire others by sharing your experience working with JLM.</p>
                </div>

    <!-- Why Share Your Experience Section -->
    <div class="info-section">
        <h2 class="info-title">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Why Share Your Experience?
        </h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-icon">💡</div>
                <div class="info-content">
                    <h4>Help Us Improve</h4>
                    <p>Your feedback helps us enhance our services and deliver better results for future clients.</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">🌟</div>
                <div class="info-content">
                    <h4>Inspire Others</h4>
                    <p>Your success story can inspire other businesses to take their projects to the next level.</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">🤝</div>
                <div class="info-content">
                    <h4>Build Trust</h4>
                    <p>Authentic testimonials help build trust and credibility in our creative community.</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">📈</div>
                <div class="info-content">
                    <h4>Showcase Results</h4>
                    <p>Share how our collaboration helped you achieve your business goals and objectives.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Testimonial Form Card -->
    <div class="card">
        <form method="POST" action="" id="testimonialForm">
            <input type="hidden" name="testimonial_form" value="1">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Personal Information -->
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required minlength="2" maxlength="100" 
                               value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>"
                               aria-label="Enter your full name">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" maxlength="100"
                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                               aria-label="Enter your email address (optional)">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="role">Your Role/Position</label>
                        <input type="text" id="role" name="role" maxlength="100"
                               value="<?php echo htmlspecialchars($formData['role'] ?? ''); ?>"
                               placeholder="e.g., CEO, Marketing Manager, Freelancer"
                               aria-label="Enter your role or position (optional)">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="company">Company/Organization</label>
                        <input type="text" id="company" name="company" maxlength="100"
                               value="<?php echo htmlspecialchars($formData['company'] ?? ''); ?>"
                               placeholder="e.g., Tech Corp, Self-employed"
                               aria-label="Enter your company or organization (optional)">
                    </div>
                </div>
            </div>

            <!-- Rating Section -->
            <div class="form-group">
                <label for="rating">Overall Rating *</label>
                <div class="rating-container" role="radiogroup" aria-labelledby="rating-label">
                    <span class="sr-only" id="rating-label">Select your rating from 1 to 5 stars</span>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="rating-star" data-rating="<?php echo $i; ?>" 
                                aria-label="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>"
                                tabindex="0">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" required value="<?php echo htmlspecialchars($formData['rating'] ?? ''); ?>">
            </div>

            <!-- Testimonial Text -->
            <div class="form-group">
                <label for="text">Your Experience *</label>
                <textarea id="text" name="text" required minlength="10" maxlength="1000" 
                          placeholder="Share your experience working with JLM. What did you like? How did we help you achieve your goals? What would you tell others about working with us?"
                          aria-label="Share your experience with JLM"><?php echo htmlspecialchars($formData['text'] ?? ''); ?></textarea>
                <div class="text-sm text-gray-500 mt-1">
                    <span id="charCount">0</span>/1000 characters
                </div>
            </div>

            <!-- Submit Button -->
            <div class="action-row">
                <button type="submit" class="submit-btn">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    Submit Testimonial
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Rating functionality
    const ratingStars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('ratingInput');
    
    ratingStars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            ratingInput.value = rating;
            
            // Update visual state - highlight this star and all stars before it
            ratingStars.forEach((s, index) => {
                const starRating = index + 1; // Convert array index to star rating (1-5)
                if (starRating <= rating) {
                    s.classList.add('selected');
                } else {
                    s.classList.remove('selected');
                }
            });
        });
        
        // Hover effects for preview
        star.addEventListener('mouseenter', function() {
            const hoverRating = parseInt(this.dataset.rating);
            ratingStars.forEach((s, index) => {
                const starRating = index + 1;
                if (starRating <= hoverRating) {
                    s.classList.add('hover');
                } else {
                    s.classList.remove('hover');
                }
            });
        });
        
        star.addEventListener('mouseleave', function() {
            // Remove hover effects when mouse leaves
            ratingStars.forEach(s => {
                s.classList.remove('hover');
            });
        });
        
        // Also handle mouseover for better hover experience
        star.addEventListener('mouseover', function() {
            const hoverRating = parseInt(this.dataset.rating);
            ratingStars.forEach((s, index) => {
                const starRating = index + 1;
                if (starRating <= hoverRating) {
                    s.classList.add('hover');
                } else {
                    s.classList.remove('hover');
                }
            });
        });
        
        // Keyboard navigation
        star.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // Character count
    const textarea = document.getElementById('text');
    const charCount = document.getElementById('charCount');
    
    textarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });
    
               // Form validation
           const form = document.getElementById('testimonialForm');
           form.addEventListener('submit', function(e) {
               if (!ratingInput.value) {
                   e.preventDefault();
                   showToast('Please select a rating before submitting.', 'error');
                   return;
               }
           });
    
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
    
         // Handle PHP result messages
     <?php if ($testimonial_result): ?>
     showToast('<?php echo addslashes($testimonial_result['message']); ?>', 
               '<?php echo $testimonial_result['success'] ? 'success' : 'error'; ?>');
     <?php endif; ?>
});
</script>
</body>
</html> 