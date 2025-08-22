<?php
// Centralized session management - prevent conflicts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if database connection exists, if not include config
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/config.php';
}

// Include performance optimizations
require_once __DIR__ . '/performance.php';

// Include security features
require_once __DIR__ . '/security.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Security: Set secure headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// Check for blocked IP
$client_ip = get_client_ip();
if (is_ip_blocked($client_ip)) {
    http_response_code(403);
    die('Access denied. Your IP address has been temporarily blocked due to suspicious activity.');
}

// Detect suspicious activity
$threats = detect_suspicious_activity();
if (!empty($threats)) {
    foreach ($threats as $threat) {
        log_security_event('threat_detected', "Suspicious activity detected: {$threat['type']}", $threat);
    }
    
    // Block IP for 1 hour if multiple threats detected
    if (count($threats) >= 3) {
        block_ip($client_ip, 3600, 'Multiple security threats detected');
        http_response_code(403);
        die('Access denied. Multiple security threats detected.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($page_title) ? $page_title . ' - JLM' : 'JLM Creative Media'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'JLM Creative Media - Transforming ideas into creative excellence through innovative media production and personalized service across Nigeria and beyond.'; ?>">
    <meta name="keywords" content="JLM, Creative Media, Video Production, Branding, Design, Nigeria, Agency, Portfolio, Testimonials, Blog, Contact">
    
    <!-- Security: CSP meta tag as fallback -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; img-src 'self' data: https:; font-src 'self' https: data:;">
    
    <!-- Tailwind CSS -->
    <script>
        tailwind = window.tailwind || {};
        tailwind.config = {
            safelist: [
                'hover:scale-105', 'group-hover:scale-105', 'hover:shadow-2xl', 'hover:shadow-xl', 'hover:-translate-y-2', 'hover:bg-gray-100',
                'transition-all', 'duration-300', 'duration-200', 'hover:bg-white/80', 'hover:bg-gray-50',
                'opacity-0', 'opacity-100'
            ]
        };
    </script>
    <script src="https://cdn.tailwindcss.com?ts=1690000000"></script>
    
    <!-- Custom Styles -->
    <style>
        main.opacity-0 {
            opacity: 0;
            transform: scale(0.98) translateY(20px);
        }
        main.opacity-100 {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
        main {
            transition: opacity 0.5s, transform 0.5s;
        }
        .section-fade {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s cubic-bezier(0.4,0,0.2,1), transform 0.8s cubic-bezier(0.4,0,0.2,1);
        }
        .section-visible {
            opacity: 1 !important;
            transform: none !important;
        }
        .animated-border {
            position: relative;
            z-index: 1;
        }
        .animated-border::before {
            content: '';
            position: absolute;
            inset: -2px;
            z-index: -1;
            border-radius: inherit;
            background: linear-gradient(120deg, #1b2299, #e4306d, #f7de7a, #1b2299);
            background-size: 300% 300%;
            opacity: 0;
            transition: opacity 0.4s;
            animation: borderMove 2s linear infinite;
        }
        .animated-border:hover::before, .animated-border:focus::before, .animated-border:active::before {
            opacity: 1;
        }
        @keyframes borderMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            background: rgba(255,255,255,0.5);
            pointer-events: none;
            z-index: 10;
        }
        @keyframes ripple {
            to {
                transform: scale(2.5);
                opacity: 0;
            }
        }
        .ripple {
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .fade-in-on-load {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s ease-out forwards;
        }
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        /* Performance: Optimize animations */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Consistent form styling for testimonial form */
        .testimonial-form input[type="text"], 
        .testimonial-form input[type="email"], 
        .testimonial-form textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ececf0;
            border-radius: 8px;
            font-size: 1.05rem;
            background: #f7f7fa;
            color: #222;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        
        .testimonial-form input:focus, 
        .testimonial-form textarea:focus {
            outline: 2px solid #bdb6f6;
            border-color: #bdb6f6;
            background: #fff;
        }
        
        .testimonial-form label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #222;
            font-size: 1.02rem;
        }
    </style>
    <link rel="icon" type="image/png" href="assets/images/jlm.png">
</head>
<body class="bg-white">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <a href="index.php" class="flex items-center space-x-2">
                    <img src="assets/images/jlm.png" alt="JLM Logo" class="h-8 w-auto">
                    <span class="text-xl font-bold text-gray-900">JLM Creative Media</span>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex space-x-8">
                    <a href="index.php#home" class="<?php echo $current_page === 'index' ? 'text-blue-600' : 'text-gray-700'; ?> hover:text-blue-600 transition-colors duration-200">Home</a>
                    <a href="index.php#about" class="<?php echo $current_page === 'index' ? 'text-blue-600' : 'text-gray-700'; ?> hover:text-blue-600 transition-colors duration-200">About</a>
                    <a href="index.php#services" class="<?php echo $current_page === 'index' ? 'text-blue-600' : 'text-gray-700'; ?> hover:text-blue-600 transition-colors duration-200">Services</a>
                    <a href="index.php#portfolio" class="<?php echo $current_page === 'index' ? 'text-blue-600' : 'text-gray-700'; ?> hover:text-blue-600 transition-colors duration-200">Portfolio</a>
                    <a href="index.php#testimonials" class="<?php echo $current_page === 'index' ? 'text-blue-600' : 'text-gray-700'; ?> hover:text-blue-600 transition-colors duration-200">Testimonials</a>
                    <a href="index.php#contact" class="<?php echo $current_page === 'index' ? 'text-blue-600' : 'text-gray-700'; ?> hover:text-blue-600 transition-colors duration-200">Contact</a>
                    <a href="booking.php" class="<?php echo $current_page === 'booking' ? 'text-blue-600' : 'text-gray-700'; ?> hover:text-blue-600 transition-colors duration-200">Book Consultation</a>
                    <a href="blog.php" class="<?php echo $current_page === 'blog' ? 'text-blue-600' : 'text-gray-700'; ?> hover:text-blue-600 transition-colors duration-200">Blog</a>
                </nav>

                <!-- Mobile menu button -->
                <button class="md:hidden mobile-menu-button p-2 rounded-md text-gray-700 hover:text-blue-600 hover:bg-gray-100 transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <div class="md:hidden mobile-menu hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t">
                    <a href="index.php#home" class="block px-3 py-2 text-gray-700 hover:text-blue-600 transition-colors duration-200">Home</a>
                    <a href="index.php#about" class="block px-3 py-2 text-gray-700 hover:text-blue-600 transition-colors duration-200">About</a>
                    <a href="index.php#services" class="block px-3 py-2 text-gray-700 hover:text-blue-600 transition-colors duration-200">Services</a>
                    <a href="index.php#portfolio" class="block px-3 py-2 text-gray-700 hover:text-blue-600 transition-colors duration-200">Portfolio</a>
                    <a href="index.php#testimonials" class="block px-3 py-2 text-gray-700 hover:text-blue-600 transition-colors duration-200">Testimonials</a>
                    <a href="index.php#contact" class="block px-3 py-2 text-gray-700 hover:text-blue-600 transition-colors duration-200">Contact</a>
                    <a href="booking.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 transition-colors duration-200">Book Consultation</a>
                    <a href="blog.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 transition-colors duration-200">Blog</a>
                </div>
            </div>
        </div>
    </header>

    <main class="min-h-screen"> 