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

// Include global functions for asset_url() and other helpers
require_once __DIR__ . '/functions.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get current page for navigation highlighting unless a page already set one
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
}

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
    <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title . ' - JLM') : 'JLM Creative Media'; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'JLM Creative Media'; ?>">
    <meta property="og:type" content="website">
    <?php if (!empty($page_og_image)): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($page_og_image); ?>">
    <?php endif; ?>
    <?php if (!empty($page_canonical)): ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($page_canonical); ?>">
    <?php endif; ?>
    
    <!-- Security: CSP meta tag as fallback -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; img-src 'self' data: https:; font-src 'self' https: data:;">
    
    <!-- Font Awesome (used by blog pages) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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
    <link rel="shortcut icon" href="<?php echo asset_url('favicon.ico'); ?>" type="image/x-icon">
    <link rel="icon" href="<?php echo asset_url('favicon.ico'); ?>" type="image/x-icon">
    <link rel="icon" type="image/png" href="<?php echo asset_url('assets/images/jlm.png'); ?>">
</head>
<body class="bg-white text-gray-900">
    <!-- Header (match homepage look) -->
    <header class="sticky top-0 z-50 bg-white/60 backdrop-blur-md border-b border-white/30 shadow-sm">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="index.php#home" class="flex items-center gap-2" aria-label="Go to home section">
                        <img src="<?php echo asset_url('assets/images/jlm.png'); ?>" alt="JLM logo, home" class="h-10 w-auto" />
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-8" aria-label="Main Navigation">
                    <a href="index.php#home" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
                        Home
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="index.php#services" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
                        Services
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="index.php#portfolio" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
                        Portfolio
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="index.php#about" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
                        About
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="index.php#testimonials" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
                        Testimonials
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="blog.php" class="<?php echo $current_page === 'blog' ? 'text-[#1b2299]' : 'text-gray-700'; ?> hover:text-[#1b2299] transition-colors duration-200 relative group">
                        Blog
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="index.php#contact" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
                        Contact
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
                    </a>
                </nav>

                <?php
                if (!function_exists('jlm_get_blog_user')) {
                    require_once __DIR__ . '/blog_auth_helpers.php';
                }
                $hdrBlogUser  = jlm_get_blog_user();
                $hdrInitial   = '';
                $hdrLogoutUrl = 'blog_logout.php';
                if ($hdrBlogUser !== null) {
                    $hdrInitial   = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $hdrBlogUser['name']), 0, 1) ?: 'U');
                    $hdrLogoutUrl = 'blog_logout.php?redirect=' . urlencode(
                        basename($_SERVER['PHP_SELF']) .
                        (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '')
                    );
                }
                ?>

                <!-- CTA Buttons -->
                <div class="hidden md:flex items-center gap-3">
                    <a href="booking.php" class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white px-6 py-2 rounded-lg shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:from-[#151d7a] hover:to-[#c2285a] flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        Book Now
                    </a>
                    <a href="index.php#contact" class="border-2 border-[#1b2299] text-[#1b2299] px-6 py-2 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-[#1b2299] hover:text-white flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v16H4V4zm0 0l8 8m0 0l8-8" /></svg>
                        Contact
                    </a>

                    <?php if (isset($hdrBlogUser) && $hdrBlogUser !== null): ?>
                    <!-- User Avatar Dropdown -->
                    <div class="relative ml-1" id="nav-user-menu-wrap">
                        <button type="button" id="nav-user-btn"
                                class="w-9 h-9 rounded-full bg-gradient-to-br from-[#1b2299] to-[#e4306d] text-white flex items-center justify-center font-bold text-sm shadow-md hover:shadow-lg hover:scale-105 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#1b2299]/50 focus:ring-offset-2 overflow-hidden"
                                aria-label="Your account" aria-haspopup="true" aria-expanded="false">
                            <?php if (!empty($hdrBlogUser['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($hdrBlogUser['avatar_url']); ?>" alt="User avatar" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?php echo htmlspecialchars($hdrInitial); ?>
                            <?php endif; ?>
                        </button>
                        <div id="nav-user-dropdown"
                             class="hidden absolute right-0 mt-2.5 w-64 rounded-2xl bg-white shadow-2xl border border-gray-100/80 z-[9999] overflow-hidden"
                             role="menu" aria-labelledby="nav-user-btn">
                            <!-- Profile header -->
                            <div class="flex items-center gap-3 px-4 py-4 bg-gradient-to-br from-slate-50 to-white border-b border-gray-100">
                                <?php if (!empty($hdrBlogUser['avatar_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($hdrBlogUser['avatar_url']); ?>" alt="User avatar" class="w-10 h-10 rounded-full object-cover shadow flex-shrink-0">
                                <?php else: ?>
                                    <span class="w-10 h-10 rounded-full bg-gradient-to-br from-[#1b2299] to-[#e4306d] text-white flex items-center justify-center font-bold text-sm flex-shrink-0 shadow">
                                        <?php echo htmlspecialchars($hdrInitial); ?>
                                    </span>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($hdrBlogUser['name']); ?></p>
                                    <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($hdrBlogUser['email'] ?? ''); ?></p>
                                </div>
                            </div>
                            <!-- Actions -->
                            <div class="px-2 py-2">
                                <a href="<?php echo htmlspecialchars($hdrLogoutUrl); ?>"
                                   class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors duration-150"
                                   role="menuitem">
                                    <i class="fas fa-sign-out-alt w-4 text-center"></i>
                                    Sign Out
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#1b2299]" aria-label="Toggle menu">
                    <svg id="mobile-menu-icon-open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    <svg id="mobile-menu-icon-close" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="md:hidden absolute top-16 left-0 right-0 bg-white backdrop-blur-lg border border-white/30 shadow-lg rounded-xl hidden z-[9999] max-h-[calc(100vh-4rem)] overflow-y-auto">
                <nav class="flex flex-col space-y-4 p-4" aria-label="Mobile Navigation">
                    <a href="index.php#home" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Home</a>
                    <a href="index.php#services" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Services</a>
                    <a href="index.php#portfolio" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Portfolio</a>
                    <a href="index.php#about" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">About</a>
                    <a href="index.php#testimonials" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Testimonials</a>
                    <a href="blog.php" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Blog</a>
                    <a href="index.php#contact" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Contact</a>
                    <?php if (isset($hdrBlogUser) && $hdrBlogUser !== null): ?>
                        <div class="mt-2 pt-2 border-t border-gray-100">
                            <div class="flex items-center gap-2.5 px-1 py-1 mb-1">
                                <?php if (!empty($hdrBlogUser['avatar_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($hdrBlogUser['avatar_url']); ?>" alt="User avatar" class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                <?php else: ?>
                                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-[#1b2299] to-[#e4306d] text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                                        <?php echo htmlspecialchars(strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $hdrBlogUser['name']), 0, 1) ?: 'U')); ?>
                                    </span>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($hdrBlogUser['name']); ?></p>
                                    <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($hdrBlogUser['email']); ?></p>
                                </div>
                            </div>
                            <a href="<?php echo htmlspecialchars($hdrLogoutUrl ?? 'blog_logout.php'); ?>" class="flex items-center gap-2 text-red-600 hover:text-red-700 font-semibold py-2 transition-colors duration-200">
                                <i class="fas fa-sign-out-alt"></i> Sign Out
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="space-y-2 mt-4">
                        <a href="booking.php" class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white w-full block text-center py-2 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:from-[#151d7a] hover:to-[#c2285a]">Book Now</a>
                        <a href="index.php#contact" class="border-2 border-[#1b2299] text-[#1b2299] w-full block text-center py-2 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-[#1b2299] hover:text-white">Contact</a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <script>
        // Mobile menu toggle logic (match homepage behavior)
        (function () {
            const menuToggle = document.getElementById('mobile-menu-toggle');
            const mobileMenu = document.getElementById('mobile-menu');
            const iconOpen = document.getElementById('mobile-menu-icon-open');
            const iconClose = document.getElementById('mobile-menu-icon-close');
            if (!menuToggle || !mobileMenu || !iconOpen || !iconClose) return;
            menuToggle.addEventListener('click', function () {
                const isOpen = !mobileMenu.classList.contains('hidden');
                mobileMenu.classList.toggle('hidden');
                iconOpen.classList.toggle('hidden');
                iconClose.classList.toggle('hidden');
                menuToggle.setAttribute('aria-expanded', String(!isOpen));
            });
        })();

        // User avatar dropdown toggle
        (function () {
            const btn = document.getElementById('nav-user-btn');
            const menu = document.getElementById('nav-user-dropdown');
            if (!btn || !menu) return;

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isOpen = !menu.classList.contains('hidden');
                menu.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', String(!isOpen));
            });

            // Close when clicking anywhere outside
            document.addEventListener('click', function (e) {
                if (!menu.contains(e.target) && e.target !== btn) {
                    menu.classList.add('hidden');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    menu.classList.add('hidden');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        })();
    </script>

    <main class="min-h-screen"> 
