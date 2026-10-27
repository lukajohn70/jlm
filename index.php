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
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/email_utils.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$portfolio_items = get_portfolio_items(null, 12);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>JLM</title>
  <!-- Tailwind CDN for rapid prototyping (replace with built CSS in production) -->
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="<?php echo asset_url('favicon.ico'); ?>" type="image/x-icon">
  <link rel="icon" href="<?php echo asset_url('favicon.ico'); ?>" type="image/x-icon">
  <link rel="icon" type="image/png" href="<?php echo asset_url('assets/images/jlm.png'); ?>">
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
/* Section fade-in on scroll */
.section-fade {
  opacity: 0;
  transform: translateY(40px);
  transition: opacity 0.8s cubic-bezier(0.4,0,0.2,1), transform 0.8s cubic-bezier(0.4,0,0.2,1);
}
.section-visible {
  opacity: 1 !important;
  transform: none !important;
}
/* Animated gradient border for cards */
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
/* Ripple effect for buttons */
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
  animation: fadeInMain 1.2s cubic-bezier(0.4,0,0.2,1) 0.1s forwards;
}
@keyframes fadeInMain {
  to { opacity: 1; }
}
.footer-socials a svg {
  transition: color 0.2s, fill 0.2s;
}
.footer-socials a:hover svg {
  color: #e4306d !important;
  fill: #e4306d !important;
}
</style>
  <meta name="description" content="JLM – Creative Media Agency: Video production, branding, design, and digital solutions in Nigeria.">
  <meta name="keywords" content="JLM, Creative Media, Video Production, Branding, Design, Nigeria, Agency, Portfolio, Testimonials, Blog, Contact">
  <meta property="og:title" content="JLM – Creative Media Agency" />
  <meta property="og:description" content="Transforming ideas into creative excellence through innovative media production and personalized service across Nigeria and beyond." />
  <meta property="og:image" content="<?php echo asset_url('assets/images/jlm.png'); ?>" />
  <meta property="og:type" content="website" />
</head>
<body class="bg-white text-gray-900">
  <!-- Header (React-inspired, exact replica) -->
  <header class="sticky top-0 z-50 bg-white/60 backdrop-blur-md border-b border-white/30 shadow-sm">
    <div class="container mx-auto px-4 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center">
          <a href="#home" class="flex items-center gap-2" aria-label="Go to home section">
            <img src="<?php echo asset_url('assets/images/jlm.png'); ?>" alt="JLM logo, home" class="h-10 w-auto" />
          </a>
        </div>
        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center space-x-8" aria-label="Main Navigation">
          <a href="#home" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
            Home
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
          </a>
          <a href="#services" onclick="scrollToSection(event, '#services')" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
            Services
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
          </a>
          <a href="#portfolio" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
            Portfolio
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
          </a>
          <a href="#about" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
            About
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
          </a>
          <a href="#testimonials" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
            Testimonials
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
          </a>
          <a href="blog.php" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
            Blog
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
          </a>
          <a href="#contact" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
            Contact
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d] group-hover:w-full transition-all duration-300"></span>
          </a>
        </nav>
        <!-- CTA Buttons -->
        <div class="hidden md:flex items-center space-x-3">
          <a href="booking.php" class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white px-6 py-2 rounded-lg shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:from-[#151d7a] hover:to-[#c2285a] flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            Book Now
          </a>
          <a href="#contact" class="border-2 border-[#1b2299] text-[#1b2299] px-6 py-2 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-[#1b2299] hover:text-white flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v16H4V4zm0 0l8 8m0 0l8-8" /></svg>
            Contact
          </a>
        </div>
        <!-- Mobile Menu Button -->
        <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#1b2299]">
          <svg id="mobile-menu-icon-open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
          <svg id="mobile-menu-icon-close" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      <!-- Mobile Menu -->
      <div id="mobile-menu" class="md:hidden absolute top-16 left-0 right-0 bg-white backdrop-blur-lg border border-white/30 shadow-lg rounded-xl hidden z-[9999] max-h-[calc(100vh-4rem)] overflow-y-auto">
        <nav class="flex flex-col space-y-4 p-4">
          <a href="#home" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Home</a>
          <a href="#services" onclick="scrollToSection(event, '#services')" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Services</a>
          <a href="#portfolio" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Portfolio</a>
          <a href="#about" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">About</a>
          <a href="#testimonials" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Testimonials</a>
          <a href="blog.php" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Blog</a>
          <a href="#contact" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Contact</a>
          <div class="space-y-2 mt-4">
            <a href="booking.php" class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white w-full block text-center py-2 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:from-[#151d7a] hover:to-[#c2285a]">Book Now</a>
            <a href="#contact" class="border-2 border-[#1b2299] text-[#1b2299] w-full block text-center py-2 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-[#1b2299] hover:text-white">Contact</a>
          </div>
        </nav>
      </div>
    </div>
  </header>
  <script>
    // Mobile menu toggle logic
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const iconOpen = document.getElementById('mobile-menu-icon-open');
    const iconClose = document.getElementById('mobile-menu-icon-close');
    if (menuToggle && mobileMenu && iconOpen && iconClose) {
      menuToggle.addEventListener('click', function() {
        const isOpen = !mobileMenu.classList.contains('hidden');
        if (isOpen) {
          mobileMenu.classList.add('hidden');
          iconOpen.classList.remove('hidden');
          iconClose.classList.add('hidden');
        } else {
          mobileMenu.classList.remove('hidden');
          iconOpen.classList.add('hidden');
          iconClose.classList.remove('hidden');
        }
      });
      document.querySelectorAll('#mobile-menu nav a').forEach(function(link) {
        link.addEventListener('click', function() {
          mobileMenu.classList.add('hidden');
          iconOpen.classList.remove('hidden');
          iconClose.classList.add('hidden');
        });
      });
    }
  </script>

  <main class="transition-all duration-500 opacity-100 fade-in-on-load">
    <!-- Hero Section -->
    <section id="home" class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-50 to-white">
      <div class="absolute inset-0 bg-gradient-to-r from-[#1b2299]/5 to-[#e4306d]/5 pointer-events-none"></div>
      <div class="absolute top-20 left-10 w-32 h-32 bg-[#f7de7a]/20 rounded-full blur-xl animate-pulse pointer-events-none"></div>
      <div class="absolute bottom-20 right-10 w-48 h-48 bg-[#e4306d]/20 rounded-full blur-xl animate-pulse pointer-events-none"></div>
      <div class="container mx-auto px-4 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div class="text-center lg:text-left">
            <div class="inline-flex items-center space-x-2 bg-white/80 backdrop-blur-sm border border-[#1b2299]/20 rounded-full px-4 py-2 mb-6">
              <span class="text-sm font-medium text-gray-700">Award-Winning Creative Team</span>
            </div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
              Bring Your <span class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] bg-clip-text text-transparent">Creative Vision</span> to Life
            </h1>
            <p class="text-xl text-gray-600 mb-8 max-w-2xl">
              JLM delivers world-class video production, music creation, livestreaming, motion graphics, web design, and media consultancy services. We bring your vision to life with innovative techniques and personalized service.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
              <a href="booking.php" class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white px-6 py-2 rounded-lg shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:from-[#151d7a] hover:to-[#c2285a] flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Book Now
              </a>
              <a href="#portfolio" class="border-2 border-[#1b2299] text-[#1b2299] px-8 py-3 text-lg rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-[#1b2299] hover:text-white flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-4.03 9-9 9S3 17 3 12 7.03 3 12 3s9 4.03 9 9z" /></svg>
                View Portfolio
              </a>
            </div>
          </div>
          <div class="relative">
            <img src="https://images.unsplash.com/photo-1576633587382-13ddf37b1fc1?w=600&h=600&fit=crop&crop=faces" alt="Creative media production" class="w-full h-[500px] object-cover rounded-2xl shadow-2xl" />
            <div class="absolute inset-0 bg-gradient-to-tr from-[#1b2299]/20 to-[#e4306d]/20 rounded-2xl pointer-events-none"></div>
            <div class="absolute top-2 left-2 sm:-top-6 sm:-left-8 bg-white/60 backdrop-blur-md border border-white/30 p-4 rounded-xl shadow-lg animate-pulse">
              <span class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-pink-300 animate-pulse"></span>
                <span class="text-sm font-medium">Live</span>
              </span>
            </div>
            <div class="absolute bottom-4 right-4 bg-white/80 backdrop-blur-md border border-white/30 p-2 rounded-lg shadow-lg animate-pulse
                text-xs font-semibold min-w-[70px] flex flex-col items-center justify-center z-20">
              <div class="font-medium">15+ Years</div>
              <div class="text-gray-500" style="font-size:10px;">Experience</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-20 bg-gray-50">
      <div class="container mx-auto px-4 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl mb-4">Our <span class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] bg-clip-text text-transparent">Services</span></h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">We offer comprehensive creative media solutions tailored to bring your vision to life. From concept to delivery, we ensure quality and innovation in every project.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div class="group cursor-pointer">
            <div class="shadow-lg transition-transform duration-300 hover:shadow-2xl hover:scale-105 bg-white/60 rounded-xl p-8 min-h-[420px]">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-[#1b2299] to-[#1b2299]/80 flex items-center justify-center mb-6 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 group-hover:shadow-xl">
                <!-- Video Camera Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3" y="7" width="13" height="10" rx="2" stroke-width="2"/><path d="M16 9l5-3v12l-5-3" stroke-width="2"/></svg>
              </div>
              <h3 class="text-xl mb-4 group-hover:text-[#1b2299] transition-colors duration-300">Video Production</h3>
              <p class="text-gray-600 leading-relaxed transition-colors duration-300 group-hover:text-gray-800">Professional video content creation from concept to final cut, including commercials, documentaries, and corporate videos.</p>
            </div>
          </div>
          <div class="group cursor-pointer">
            <div class="shadow-lg transition-transform duration-300 hover:shadow-2xl hover:scale-105 bg-white/60 rounded-xl p-8 min-h-[420px]">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-[#e4306d] to-[#e4306d]/80 flex items-center justify-center mb-6 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 group-hover:shadow-xl">
                <!-- Music Note Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 19V6l12-2v13" stroke-width="2"/><circle cx="6" cy="18" r="3" stroke-width="2"/><circle cx="18" cy="16" r="3" stroke-width="2"/></svg>
              </div>
              <h3 class="text-xl mb-4 group-hover:text-[#e4306d] transition-colors duration-300">Music Production</h3>
              <p class="text-gray-600 leading-relaxed transition-colors duration-300 group-hover:text-gray-800">Complete music production services including recording, mixing, mastering, and sound design for all genres.</p>
            </div>
          </div>
          <div class="group cursor-pointer">
            <div class="shadow-lg transition-transform duration-300 hover:shadow-2xl hover:scale-105 bg-white/60 rounded-xl p-8 min-h-[420px]">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-[#f7de7a] to-[#f7de7a]/80 flex items-center justify-center mb-6 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 group-hover:shadow-xl">
                <!-- Broadcast Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="2" stroke-width="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.48M7.76 7.76a6 6 0 0 0 0 8.48M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14" stroke-width="2"/></svg>
              </div>
              <h3 class="text-xl mb-4 group-hover:text-[#f7de7a] transition-colors duration-300">Livestreaming</h3>
              <p class="text-gray-600 leading-relaxed transition-colors duration-300 group-hover:text-gray-800">Multi-platform livestreaming solutions for events, webinars, concerts, and real-time content delivery.</p>
            </div>
          </div>
          <div class="group cursor-pointer">
            <div class="shadow-lg transition-transform duration-300 hover:shadow-2xl hover:scale-105 bg-white/60 rounded-xl p-8 min-h-[420px]">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-[#1b2299] to-[#e4306d] flex items-center justify-center mb-6 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 group-hover:shadow-xl">
                <!-- Sparkle Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364-6.364l-1.414 1.414M6.05 17.95l-1.414 1.414m0-14.142l1.414 1.414M17.95 17.95l1.414 1.414" stroke-width="2"/></svg>
              </div>
              <h3 class="text-xl mb-4 group-hover:text-[#e4306d] transition-colors duration-300">Motion Graphics</h3>
              <p class="text-gray-600 leading-relaxed transition-colors duration-300 group-hover:text-gray-800">Dynamic motion graphics and animation services for branding, explainer videos, and visual storytelling.</p>
            </div>
          </div>
          <div class="group cursor-pointer">
            <div class="shadow-lg transition-transform duration-300 hover:shadow-2xl hover:scale-105 bg-white/60 rounded-xl p-8 min-h-[420px]">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-[#e4306d] to-[#f7de7a] flex items-center justify-center mb-6 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 group-hover:shadow-xl">
                <!-- Paintbrush Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15.232 5.232l3.536 3.536M9 11l6.586-6.586a2 2 0 1 1 2.828 2.828L11.828 13.828a4 4 0 1 1-2.828-2.828z" stroke-width="2"/></svg>
              </div>
              <h3 class="text-xl mb-4 group-hover:text-[#1b2299] transition-colors duration-300">Graphics Design</h3>
              <p class="text-gray-600 leading-relaxed transition-colors duration-300 group-hover:text-gray-800">Creative graphic design solutions including branding, marketing materials, and digital asset creation.</p>
            </div>
          </div>
          <div class="group cursor-pointer">
            <div class="shadow-lg transition-transform duration-300 hover:shadow-2xl hover:scale-105 bg-white/60 rounded-xl p-8 min-h-[420px]">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-[#f7de7a] to-[#1b2299] flex items-center justify-center mb-6 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 group-hover:shadow-xl">
                <!-- Bar Chart Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3" y="12" width="4" height="8" rx="1" stroke-width="2"/><rect x="9" y="8" width="4" height="12" rx="1" stroke-width="2"/><rect x="15" y="4" width="4" height="16" rx="1" stroke-width="2"/></svg>
              </div>
              <h3 class="text-xl mb-4 group-hover:text-[#1b2299] transition-colors duration-300">Media Consultancy</h3>
              <p class="text-gray-600 leading-relaxed transition-colors duration-300 group-hover:text-gray-800">Strategic media consulting to optimize your content strategy, distribution, and audience engagement.</p>
            </div>
          </div>
          <div class="group cursor-pointer">
            <div class="shadow-lg transition-transform duration-300 hover:shadow-2xl hover:scale-105 bg-white/60 rounded-xl p-8 min-h-[420px]">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-[#1b2299]/80 to-[#e4306d]/80 flex items-center justify-center mb-6 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 group-hover:shadow-xl">
                <!-- Graduation Cap Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m0 0H6m6 0h6" stroke-width="2"/></svg>
              </div>
              <h3 class="text-xl mb-4 group-hover:text-[#1b2299] transition-colors duration-300">E-Learning Services</h3>
              <p class="text-gray-600 leading-relaxed transition-colors duration-300 group-hover:text-gray-800">Educational content development, course creation, and interactive learning experience design.</p>
            </div>
          </div>
          <div class="group cursor-pointer">
            <div class="shadow-lg transition-transform duration-300 hover:shadow-2xl hover:scale-105 bg-white/60 rounded-xl p-8 min-h-[420px]">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-[#f7de7a]/80 to-[#1b2299]/80 flex items-center justify-center mb-6 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 group-hover:shadow-xl">
                <!-- Monitor Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="2" y="4" width="20" height="14" rx="2" stroke-width="2"/><path d="M8 20h8" stroke-width="2"/></svg>
              </div>
              <h3 class="text-xl mb-4 group-hover:text-[#1b2299] transition-colors duration-300">Web Design</h3>
              <p class="text-gray-600 leading-relaxed transition-colors duration-300 group-hover:text-gray-800">Modern, responsive website design and development focused on user experience and conversion optimization.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Portfolio Section -->
    <section id="portfolio" class="py-20 bg-white">
      <div class="container mx-auto px-4 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl mb-4">Our <span class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] bg-clip-text text-transparent">Portfolio</span></h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8">Explore our diverse range of creative projects and see how we've helped brands and artists bring their visions to life through innovative media solutions.</p>
        </div>
        
        <?php if (!empty($portfolio_items)): ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <?php foreach ($portfolio_items as $cat): 
            $type = $cat['type'] ?? 'link';
            $externalUrl = htmlspecialchars($cat['external_url'] ?? '');
            $title = htmlspecialchars($cat['title']);
            $children = get_portfolio_items($cat['id']);
            $displayImage = get_portfolio_display_image($cat);
          ?>
          <div class="group relative overflow-hidden rounded-xl shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 hover:scale-105 bg-white animated-border portfolio-card">
            <?php if (!empty($displayImage)): ?>
            <img src="<?php echo htmlspecialchars($displayImage); ?>" alt="<?php echo $title; ?>" class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-500" />
            <?php elseif (!empty($externalUrl) && $type === 'link'): 
              $parsedUrl = parse_url($externalUrl);
              $domain = isset($parsedUrl['host']) ? preg_replace('/^www\./', '', $parsedUrl['host']) : 'Website';
            ?>
            <div class="w-full h-64 bg-gradient-to-br from-[#1b2299]/10 to-[#e4306d]/10 flex flex-col items-center justify-center group-hover:scale-110 transition-transform duration-500 border-b border-gray-100">
               <img src="https://www.google.com/s2/favicons?domain=<?php echo urlencode($domain); ?>&sz=128" alt="Favicon" class="w-20 h-20 rounded-2xl shadow-lg mb-4 bg-white p-2">
               <span class="text-gray-700 font-semibold text-lg px-4 text-center break-all"><?php echo htmlspecialchars($domain); ?></span>
            </div>
            <?php endif; ?>
            <div class="p-6 transition-colors duration-300 group-hover:bg-gray-50 h-full">
              <span class="inline-block bg-[#1b2299]/10 text-[#1b2299] px-3 py-1 rounded-full text-xs mb-3"><?php echo htmlspecialchars($cat['category']); ?></span>
              <h3 class="mb-2 group-hover:text-[#1b2299] transition-colors duration-300"><?php echo $title; ?></h3>
              <p class="text-gray-600 text-sm transition-colors duration-300 group-hover:text-gray-800"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></p>
              
              <?php if ($type === 'link' && !empty($externalUrl)): ?>
              <a href="<?php echo $externalUrl; ?>" target="_blank" rel="noopener" class="inline-flex items-center mt-4 text-[#e4306d] font-semibold hover:text-[#1b2299] transition-colors">
                View Project
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5h5m0 0v5m0-5L10 14" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10v9h9" /></svg>
              </a>
              <?php endif; ?>
            </div>

            <!-- Interaction Overlays based on Type -->
            <?php if ($type !== 'link'): ?>
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex items-end justify-center opacity-0 group-hover:opacity-100 transition-all duration-500 translate-y-8 group-hover:translate-y-0 pointer-events-none group-hover:pointer-events-auto">
              <?php if ($type === 'video'): ?>
                <button onclick="openVideoModal('<?php echo $externalUrl; ?>', '<?php echo $title; ?>')" class="mb-8 px-6 py-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-lg shadow-lg font-medium transition-all duration-200 hover:scale-105 ripple flex items-center gap-2">
                  <i class="fas fa-play text-xs"></i> Watch Video
                </button>
              <?php elseif ($type === 'music'): ?>
                <button onclick="openDynamicModal('musicModal-<?php echo $cat['id']; ?>')" class="mb-8 px-6 py-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-lg shadow-lg font-medium transition-all duration-200 hover:scale-105 ripple flex items-center gap-2">
                  <i class="fas fa-headphones text-xs"></i> Listen Now
                </button>
              <?php elseif ($type === 'channel'): ?>
                <button onclick="openChannelModal('<?php echo $externalUrl; ?>', '<?php echo $title; ?>')" class="mb-8 px-6 py-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-lg shadow-lg font-medium transition-all duration-200 hover:scale-105 ripple flex items-center gap-2">
                  <i class="fab fa-youtube text-xs"></i> Visit Channel
                </button>
              <?php elseif ($type === 'web'): ?>
                <button onclick="openDynamicModal('webModal-<?php echo $cat['id']; ?>')" class="mb-8 px-6 py-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-lg shadow-lg font-medium transition-all duration-200 hover:scale-105 ripple flex items-center gap-2">
                  <i class="fas fa-globe text-xs"></i> View Websites
                </button>
              <?php elseif ($type === 'graphics'): ?>
                <button onclick="openDynamicModal('graphicsModal-<?php echo $cat['id']; ?>')" class="mb-8 px-6 py-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-lg shadow-lg font-medium transition-all duration-200 hover:scale-105 ripple flex items-center gap-2">
                  <i class="fas fa-palette text-xs"></i> View Designs
                </button>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        </div>
        <?php else: ?>
        <div class="text-center py-12">
          <p class="text-gray-500 italic">No portfolio items available at the moment.</p>
        </div>
        <?php endif; ?>
      </div>
    </section>


    <!-- About Section -->
    <section id="about" class="py-20 bg-gradient-to-br from-gray-50 to-white">
      <div class="container mx-auto px-4 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
          <div>
            <h2 class="text-3xl md:text-4xl mb-6">About <span class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] bg-clip-text text-transparent">JLM</span></h2>
            <p class="text-lg text-gray-600 mb-6 leading-relaxed">JLM is a premier creative media company based in Nigeria, dedicated to transforming ideas into compelling visual and audio experiences. Founded with a passion for storytelling and innovation, we've been helping brands and individuals amplify their voice through high-quality media production.</p>
            <p class="text-lg text-gray-600 mb-8 leading-relaxed">Our team combines technical expertise with creative vision to deliver personalized solutions that exceed expectations. From concept development to final delivery, we ensure every project reflects our commitment to excellence and innovation.</p>
            <div class="grid grid-cols-2 gap-4 mb-8">
              <div class="flex items-center space-x-3"><div class="w-2 h-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] rounded-full"></div><span class="text-gray-700">Personalized Service Approach</span></div>
              <div class="flex items-center space-x-3"><div class="w-2 h-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] rounded-full"></div><span class="text-gray-700">Fast & Reliable Delivery</span></div>
              <div class="flex items-center space-x-3"><div class="w-2 h-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] rounded-full"></div><span class="text-gray-700">Innovative Creative Techniques</span></div>
              <div class="flex items-center space-x-3"><div class="w-2 h-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] rounded-full"></div><span class="text-gray-700">Professional Quality Standards</span></div>
              <div class="flex items-center space-x-3"><div class="w-2 h-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] rounded-full"></div><span class="text-gray-700">Comprehensive Media Solutions</span></div>
              <div class="flex items-center space-x-3"><div class="w-2 h-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] rounded-full"></div><span class="text-gray-700">Collaborative Process</span></div>
            </div>
          </div>
          <div>
            <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&h=400&fit=crop" alt="JLM team at work" class="w-full h-96 object-cover rounded-2xl shadow-xl" />
            <div class="absolute inset-0 bg-gradient-to-tr from-[#1b2299]/10 to-[#e4306d]/10 rounded-2xl pointer-events-none"></div>
          </div>
        </div>
        <div class="mt-20">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="text-center border-0 shadow-lg bg-white/60 backdrop-blur-md border border-white/30 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 p-6">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 mb-4">
                <!-- Award Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#1b2299]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 17.75L18.2 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.44 4.73L5.8 21z" stroke-width="2"/></svg>
              </div>
              <div class="text-2xl font-bold mb-2 text-[#1b2299] countup" data-count="500">0+</div>
              <div class="text-gray-600 text-sm">Projects Completed</div>
            </div>
            <div class="text-center border-0 shadow-lg bg-white/60 backdrop-blur-md border border-white/30 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 p-6">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 mb-4">
                <!-- Users Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#e4306d]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" stroke-width="2"/><circle cx="9" cy="7" r="4" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke-width="2"/><path d="M16 3.13a4 4 0 0 1 0 7.75" stroke-width="2"/></svg>
              </div>
              <div class="text-2xl font-bold mb-2 text-[#e4306d] countup" data-count="300">0+</div>
              <div class="text-gray-600 text-sm">Happy Clients</div>
            </div>
            <div class="text-center border-0 shadow-lg bg-white/60 backdrop-blur-md border border-white/30 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 p-6">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 mb-4">
                <!-- Clock Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#f7de7a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 6v6l4 2" stroke-width="2"/></svg>
              </div>
              <div class="text-2xl font-bold mb-2 text-[#f7de7a] countup" data-count="15">0+</div>
              <div class="text-gray-600 text-sm">Years Experience</div>
            </div>
            <div class="text-center border-0 shadow-lg bg-white/60 backdrop-blur-md border border-white/30 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 p-6">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 mb-4">
                <!-- Zap Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#1b2299]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" stroke-width="2"/></svg>
              </div>
              <div class="text-2xl font-bold mb-2 text-[#1b2299] countup" data-count="24">0/7</div>
              <div class="text-gray-600 text-sm">Support Available</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section with Slider -->
    <section id="testimonials" class="py-20 bg-gradient-to-br from-[#1b2299]/5 to-[#e4306d]/5">
      <div class="container mx-auto px-4 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl mb-4">What Our <span class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] bg-clip-text text-transparent">Clients Say</span></h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8">Don't just take our word for it. Here's what our satisfied clients have to say about their experience working with JLM.</p>
          <div class="mt-8">
            <a href="testimonial.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-semibold rounded-lg hover:shadow-lg transition-all duration-200 transform hover:scale-105">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
              </svg>
              Share Your Experience
            </a>
          </div>
        </div>
        
        <?php 
        // Get approved testimonials from database
        $approved_testimonials = get_approved_testimonials(10);
        if (!empty($approved_testimonials)): 
        ?>
        <div class="relative max-w-4xl mx-auto">
          <div id="testimonial-slider" class="overflow-hidden">
            <div class="flex transition-transform duration-700" style="will-change: transform;" id="testimonial-track">
              <?php foreach ($approved_testimonials as $testimonial): ?>
              <div class="min-w-full border-0 shadow-2xl bg-white/60 backdrop-blur-md border border-white/30 p-8 md:p-12 flex flex-col md:flex-row items-center gap-8 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
                <div class="flex-shrink-0">
                  <?php 
                  $initials = strtoupper(substr($testimonial['name'], 0, 1));
                  if (strpos($testimonial['name'], ' ') !== false) {
                      $initials .= strtoupper(substr(explode(' ', $testimonial['name'])[1], 0, 1));
                  }
                  ?>
                  <div class="w-16 h-16 rounded-full object-cover border-4 border-[#1b2299] shadow-lg bg-gradient-to-br from-[#1b2299] to-[#e4306d] flex items-center justify-center text-white font-bold text-lg">
                    <?php echo htmlspecialchars($initials); ?>
                  </div>
                </div>
                <div class="flex-1 text-center md:text-left">
                  <p class="text-lg italic mb-4 text-gray-700">"<?php echo htmlspecialchars($testimonial['content'] ?? ''); ?>"</p>
                  <div class="flex items-center justify-center md:justify-start gap-2 mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <svg class="w-5 h-5 <?php echo $i <= ($testimonial['rating'] ?? 5) ? 'text-yellow-400 fill-current' : 'text-gray-300'; ?>" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                      </svg>
                    <?php endfor; ?>
                  </div>
                  <div class="font-semibold text-[#1b2299]"><?php echo htmlspecialchars($testimonial['name'] ?? 'Anonymous'); ?></div>
                  <div class="text-gray-500 text-sm">
                    <?php 
                    $role_company = [];
                    if (!empty($testimonial['role'])) $role_company[] = htmlspecialchars($testimonial['role']);
                    if (!empty($testimonial['company'])) $role_company[] = htmlspecialchars($testimonial['company']);
                    echo implode(', ', $role_company);
                    ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          
          <!-- Navigation arrows -->
          <button id="prev-btn" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all duration-200 hover:scale-110">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <button id="next-btn" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all duration-200 hover:scale-110">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </button>
        </div>
        <?php else: ?>
        <!-- Fallback when no testimonials available -->
        <div class="max-w-4xl mx-auto text-center">
          <div class="bg-white/60 backdrop-blur-md border border-white/30 p-8 md:p-12 rounded-2xl shadow-2xl">
            <div class="text-gray-500 mb-4">
              <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Be the First to Share!</h3>
            <p class="text-gray-600 mb-6">We'd love to hear about your experience with JLM Creative Media.</p>
            <a href="testimonial.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-semibold rounded-lg hover:shadow-lg transition-all duration-200 transform hover:scale-105">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
              </svg>
              Share Your Experience
            </a>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-white">
      <div class="container mx-auto px-4 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl mb-4">Let's Create Something <span class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] bg-clip-text text-transparent">Amazing</span></h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8">Ready to bring your vision to life? Get in touch with us today and let's discuss how we can help you achieve your creative goals.</p>
          <div class="flex justify-center">
            <a href="booking.php" class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white px-8 py-3 text-lg rounded-lg shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:from-[#151d7a] hover:to-[#c2285a] flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              Book a Consultation
            </a>
          </div>
        </div>
        <div class="grid lg:grid-cols-2 gap-12">
          <div class="shadow-xl border-0 bg-white p-8 rounded-2xl">
            <h3 class="text-xl mb-6">Get in Touch</h3>
            <p class="mb-6 opacity-90">We're here to help bring your creative vision to life. Whether you need video production, music creation, or any of our other services, we're just a message away.</p>
            <div class="flex items-center space-x-3 mb-4">
              <span class="text-[#1b2299]">🌍</span>
              <span>Serving clients across Nigeria and beyond</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Phone Card -->
              <div class="flex items-center gap-4 bg-white/70 backdrop-blur-md rounded-2xl shadow-lg p-5 border border-white/30 transition-transform duration-300 hover:scale-105 hover:shadow-2xl group cursor-pointer">
                <span class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-pink-200 to-pink-100 group-hover:from-[#1b2299] group-hover:to-[#e4306d] transition-all">
                  <svg class="w-6 h-6 text-pink-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm0 12a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2zm12-12a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zm0 12a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </span>
                <div>
                  <h4 class="font-semibold text-gray-900 mb-1">Phone</h4>
                  <span class="text-gray-900 font-medium">+2348150917741</span>
                </div>
              </div>
              <!-- Email Card -->
              <div class="flex items-center gap-4 bg-white/70 backdrop-blur-md rounded-2xl shadow-lg p-5 border border-white/30 transition-transform duration-300 hover:scale-105 hover:shadow-2xl group cursor-pointer">
                <span class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-purple-200 to-purple-100 group-hover:from-[#1b2299] group-hover:to-[#e4306d] transition-all">
                  <svg class="w-6 h-6 text-purple-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12l-4-4-4 4m8 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0V6a2 2 0 00-2-2H6a2 2 0 00-2 2v6"/></svg>
                </span>
                <div>
                  <h4 class="font-semibold text-gray-900 mb-1">Email</h4>
                  <span class="text-gray-900 font-medium">support@jlm.com.ng</span>
                </div>
              </div>
              <!-- Location Card -->
              <div class="flex items-center gap-4 bg-white/70 backdrop-blur-md rounded-2xl shadow-lg p-5 border border-white/30 transition-transform duration-300 hover:scale-105 hover:shadow-2xl group cursor-pointer">
                <span class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-red-200 to-red-100 group-hover:from-[#1b2299] group-hover:to-[#e4306d] transition-all">
                  <svg class="w-6 h-6 text-red-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2zm0 0c-4 0-7 2.686-7 6v2h14v-2c0-3.314-3-6-7-6z"/></svg>
                </span>
                <div>
                  <h4 class="font-semibold text-gray-900 mb-1">Location</h4>
                  <span class="text-gray-900 font-medium">Kaduna, Nigeria</span>
                </div>
              </div>
              <!-- Business Hours Card -->
              <div class="flex items-center gap-4 bg-white/70 backdrop-blur-md rounded-2xl shadow-lg p-5 border border-white/30 transition-transform duration-300 hover:scale-105 hover:shadow-2xl group cursor-pointer">
                <span class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-yellow-200 to-yellow-100 group-hover:from-[#1b2299] group-hover:to-[#e4306d] transition-all">
                  <svg class="w-6 h-6 text-yellow-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/></svg>
                </span>
                <div>
                  <h4 class="font-semibold text-gray-900 mb-1">Business Hours</h4>
                  <span class="text-gray-900 font-medium">Mon - Fri: 9AM - 6PM</span>
                </div>
              </div>
            </div>
          </div>
          <div class="shadow-xl border-0 bg-gradient-to-br from-[#1b2299] to-[#e4306d] text-white p-8 rounded-2xl">
            <h3 class="text-xl mb-6">Send us a Message</h3>
            <?php
            $contact_success = false;
            $contact_error = '';
            $contact_formData = [];
            $allowedContactServices = ['Web Design', 'Video Production', 'Graphics Design', 'Digital Marketing', 'Photography', 'Branding', 'Other'];
            
            // Check if contact form was successfully submitted (for clearing)
            if (isset($_SESSION['show_contact_success']) && $_SESSION['show_contact_success']) {
                $contact_success = true;
                unset($_SESSION['show_contact_success']);
                // Clear form data
                $contact_formData = [];
                error_log("CONTACT FORM DEBUG - Form cleared successfully");
            } else {
                // Get form data for repopulation (if there were errors)
                $contact_formData = $_POST;
                error_log("CONTACT FORM DEBUG - Form data repopulated: " . json_encode($contact_formData));
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
                // Rate limiting check
                $rate_limit = check_rate_limit('contact_form', 3, 300); // 3 attempts per 5 minutes
                if (!$rate_limit['allowed']) {
                    $wait_time = ceil($rate_limit['wait_time'] / 60);
                    $contact_error = "Too many attempts. Please wait {$wait_time} minute(s) before trying again.";
                } else {
                    // CSRF validation
                    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
                        $contact_error = 'Security validation failed. Please try again.';
                    } else {
                        $botCheck = detect_bot_form_submission($_POST, 'website', 3, 86400);
                        if ($botCheck['is_bot']) {
                            log_security_event('bot_submission_blocked', 'Blocked contact form bot submission', [
                                'reason' => $botCheck['reason'],
                                'email' => $_POST['email'] ?? '',
                                'path' => $_SERVER['REQUEST_URI'] ?? 'index.php#contact'
                            ]);
                            $_SESSION['show_contact_success'] = true;
                            echo '<script>window.location.href = "' . $_SERVER['REQUEST_URI'] . '?success=1#contact";</script>';
                            exit;
                        }

                        // Basic validation
                        $fields = ['name', 'email', 'message'];
                        foreach ($fields as $f) {
                            if (empty($_POST[$f])) {
                                $contact_error = 'Please fill in all required fields.';
                                break;
                            }
                        }
                        if (!$contact_error && !validate_email($_POST['email'] ?? '')) {
                            $contact_error = 'Please enter a valid email address.';
                        }
                        if (
                            !$contact_error &&
                            !empty($_POST['service']) &&
                            !in_array($_POST['service'], $allowedContactServices, true)
                        ) {
                            $contact_error = 'Invalid service selection.';
                        }
                if (!$contact_error) {
                    try {
                        // Generate ticket number
                        $ticketNumber = generate_ticket_number('CT');

                        if ($pdo) {
                            $stmt = $pdo->prepare("INSERT INTO contact_submissions (ticket_number, name, email, phone, service, subject, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $ticketNumber,
                                sanitize_input($_POST['name']),
                                sanitize_input($_POST['email']),
                                sanitize_input($_POST['phone'] ?? ''),
                                sanitize_input($_POST['service'] ?? ''),
                                !empty($_POST['service']) ? (sanitize_input($_POST['service']) . ' Enquiry') : 'General Enquiry',
                                sanitize_input($_POST['message']),
                                'new'
                            ]);

                            $contactId = $pdo->lastInsertId();
                            create_ticket_tracking($ticketNumber, 'contact', $contactId);
                        } else {
                            error_log("Contact submission (development mode): " . json_encode($_POST));
                        }
                        
                        // Enhanced debugging
                        error_log("CONTACT FORM DEBUG - Form submitted successfully");
                        error_log("CONTACT FORM DEBUG - Form data: " . json_encode($_POST));
                        error_log("CONTACT FORM DEBUG - Ticket number: " . $ticketNumber);
                        
                        // Send email notifications
                        $emailResult = handleContactFormEmail(
                            $_POST['name'],
                            $_POST['email'],
                            $_POST['phone'] ?? '',
                            $_POST['service'] ?? '',
                            $_POST['message'],
                            $ticketNumber
                        );
                        
                        // Enhanced debugging
                        error_log("CONTACT FORM DEBUG - Email result: " . json_encode($emailResult));
                        
                        // Set session variable for toast notification and form clearing
                        $_SESSION['show_contact_success'] = true;
                        
                        // Log email results for debugging
                        if (!$emailResult['admin'] || !$emailResult['user']) {
                            error_log("Contact email notification failed - Admin: " . ($emailResult['admin'] ? 'success' : 'failed') . ", User: " . ($emailResult['user'] ? 'success' : 'failed'));
                        } else {
                            error_log("Contact email notification successful - Both admin and user emails processed");
                        }
                        
                        // Set success flag for form clearing
                        $_SESSION['show_contact_success'] = true;
                        // Use JavaScript redirect instead of PHP header to avoid "headers already sent" error
                        echo '<script>window.location.href = "' . $_SERVER['REQUEST_URI'] . '?success=1#contact";</script>';
                        exit;
                    } catch (PDOException $e) {
                        $contact_error = 'Sorry, there was an error saving your message. Please try again.';
                        $_SESSION['show_contact_error'] = $contact_error;
                    }
                } else {
                    $_SESSION['show_contact_error'] = $contact_error;
                }
                }
                }
            }
            
            // Check for session messages
            if (isset($_GET['success']) && $_GET['success'] == '1') {
                $contact_success = true;
            }
            if (isset($_SESSION['show_contact_error'])) {
                $contact_error = $_SESSION['show_contact_error'];
                unset($_SESSION['show_contact_error']);
            }
            ?>
            <?php if ($contact_error): ?>
                <div class="text-red-600 font-semibold text-lg flex items-center gap-2 mb-4"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg><?php echo htmlspecialchars($contact_error); ?></div>
            <?php endif; ?>
            <form class="space-y-6" method="post" action="#contact">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <input type="hidden" name="form_started_at" value="<?php echo time(); ?>">
              <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off" />
              </div>
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label for="name" class="block mb-1">Full Name *</label>
                  <input id="name" name="name" type="text" required class="w-full px-4 py-2 rounded-lg bg-white text-gray-900" placeholder="Your full name" value="<?php echo isset($contact_formData['name']) ? htmlspecialchars($contact_formData['name']) : ''; ?>" />
                </div>
                <div>
                  <label for="email" class="block mb-1">Email Address *</label>
                  <input id="email" name="email" type="email" required class="w-full px-4 py-2 rounded-lg bg-white text-gray-900" placeholder="your@email.com" value="<?php echo isset($contact_formData['email']) ? htmlspecialchars($contact_formData['email']) : ''; ?>" />
                </div>
              </div>
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label for="phone" class="block mb-1">Phone Number</label>
                  <input id="phone" name="phone" type="text" class="w-full px-4 py-2 rounded-lg bg-white text-gray-900" placeholder="+234 xxx xxx xxxx" value="<?php echo isset($contact_formData['phone']) ? htmlspecialchars($contact_formData['phone']) : ''; ?>" />
                </div>
                <div>
                  <label for="service" class="block mb-1">Service Interested In</label>
                  <select id="service" name="service" class="w-full px-4 py-2 rounded-lg bg-white text-gray-900">
                    <option value="">Select a service...</option>
                    <option value="Web Design" <?php echo (isset($contact_formData['service']) && $contact_formData['service'] === 'Web Design') ? 'selected' : ''; ?>>Web Design</option>
                    <option value="Video Production" <?php echo (isset($contact_formData['service']) && $contact_formData['service'] === 'Video Production') ? 'selected' : ''; ?>>Video Production</option>
                    <option value="Graphics Design" <?php echo (isset($contact_formData['service']) && $contact_formData['service'] === 'Graphics Design') ? 'selected' : ''; ?>>Graphics Design</option>
                    <option value="Digital Marketing" <?php echo (isset($contact_formData['service']) && $contact_formData['service'] === 'Digital Marketing') ? 'selected' : ''; ?>>Digital Marketing</option>
                    <option value="Photography" <?php echo (isset($contact_formData['service']) && $contact_formData['service'] === 'Photography') ? 'selected' : ''; ?>>Photography</option>
                    <option value="Branding" <?php echo (isset($contact_formData['service']) && $contact_formData['service'] === 'Branding') ? 'selected' : ''; ?>>Branding</option>
                    <option value="Other" <?php echo (isset($contact_formData['service']) && $contact_formData['service'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                  </select>
                </div>
              </div>
              <div>
                <label for="message" class="block mb-1">Message *</label>
                                  <textarea id="message" name="message" required rows="5" class="w-full px-4 py-2 rounded-lg bg-white text-gray-900" placeholder="Tell us about your project..."><?php echo isset($contact_formData['message']) ? htmlspecialchars($contact_formData['message']) : ''; ?></textarea>
              </div>
              <div class="flex flex-col sm:flex-row gap-4">
                <button type="submit" name="contact_form" class="flex-1 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white px-6 py-3 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:from-[#151d7a] hover:to-[#c2285a] flex items-center justify-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                  Send Message
                </button>
                <a href="https://wa.me/2348150917741?text=Hello%20JLM%20Creative%2C%20I%20would%20like%20to%20enquire%20about%20your%20services." target="_blank" class="flex-1 bg-[#25D366] text-white px-6 py-3 rounded-lg text-center transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-[#128C4B] flex items-center justify-center gap-2" style="text-decoration:none;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 32 32" style="margin-right:8px;vertical-align:middle;"><path fill="#fff" d="M16 3C9.373 3 4 8.373 4 15c0 2.64.86 5.09 2.33 7.09L4 29l7.18-2.28A12.93 12.93 0 0 0 16 27c6.627 0 12-5.373 12-12S22.627 3 16 3zm0 22c-1.98 0-3.84-.58-5.41-1.58l-.39-.25-4.27 1.36 1.4-4.14-.25-.4A9.93 9.93 0 0 1 6 15c0-5.52 4.48-10 10-10s10 4.48 10 10-4.48 10-10 10zm5.07-7.75c-.28-.14-1.65-.81-1.9-.9-.25-.09-.43-.14-.61.13-.18.27-.7.92-.87 1.11-.16.19-.32.21-.6.07-.28-.14-1.17-.42-2.23-1.37-.82-.74-1.37-1.65-1.53-1.92-.16-.27-.02-.43.12-.57.13-.13.28-.33.41-.5.13-.17.18-.28.28-.47.09-.19.05-.35-.02-.48-.07-.13-.61-1.48-.84-2.03-.22-.54-.45-.46-.61-.47-.16-.01-.34-.01-.54-.01-.18 0-.48.07-.73.35-.25.28-.96.97-.96 2.37 0 1.4 1 2.75 1.14 2.94.13.18 1.97 3.01 4.77 4.12.67.28 1.19.45 1.6.57.67.21 1.28.18 1.76.11.53-.08 1.65-.68 1.88-1.32.23-.64.23-1.2.16-1.32-.07-.12-.25-.18-.53-.32z"/></svg>
                  WhatsApp
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Portfolio Modals (Outside the grid and main tag to avoid stacking context issues) -->
  <?php foreach ($portfolio_items as $cat): 
    $type = $cat['type'] ?? 'link';
    $title = htmlspecialchars($cat['title']);
    $children = get_portfolio_items($cat['id']);
    if ($type === 'link') continue;
  ?>
    <?php if ($type === 'web'): ?>
      <div id="webModal-<?php echo $cat['id']; ?>" class="fixed inset-0 bg-black bg-opacity-75 flex items-start justify-center z-[9999] p-4 overflow-y-auto portfolio-modal" style="display: none;">
        <div class="bg-white rounded-2xl p-6 max-w-6xl w-full mx-4 shadow-2xl relative my-8">
          <button onclick="closeDynamicModal('webModal-<?php echo $cat['id']; ?>')" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors z-10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center"><?php echo $title; ?> Portfolio</h3>
          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($children as $child): ?>
              <?php $childDisplayImage = get_portfolio_display_image($child); ?>
              <div class="space-y-3">
                <div class="flex items-center gap-3 mb-3">
                  <span class="inline-block bg-[#1b2299]/20 text-[#1b2299] px-3 py-1 rounded-full text-xs"><?php echo htmlspecialchars((string)($child['category'] ?? 'Portfolio')); ?></span>
                  <h4 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars((string)$child['title']); ?></h4>
                </div>
                <?php if (!empty($childDisplayImage)): ?>
                  <img src="<?php echo htmlspecialchars($childDisplayImage); ?>" alt="<?php echo htmlspecialchars($child['title']); ?>" class="w-full h-48 object-cover rounded-lg shadow-md" />
                <?php elseif (!empty($child['external_url'])): 
                  $childParsedUrl = parse_url($child['external_url']);
                  $childDomain = isset($childParsedUrl['host']) ? preg_replace('/^www\./', '', $childParsedUrl['host']) : 'Website';
                ?>
                  <div class="w-full h-48 bg-gradient-to-br from-[#1b2299]/10 to-[#e4306d]/10 rounded-lg shadow-md flex flex-col items-center justify-center border border-[#1b2299]/10">
                    <img src="https://www.google.com/s2/favicons?domain=<?php echo urlencode($childDomain); ?>&sz=128" alt="Favicon" class="w-16 h-16 rounded-xl shadow-md mb-3 bg-white p-2">
                    <span class="text-gray-700 font-medium px-2 text-center break-all"><?php echo htmlspecialchars($childDomain); ?></span>
                  </div>
                <?php endif; ?>
                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars((string)$child['description']); ?></p>
                <a href="<?php echo htmlspecialchars((string)$child['external_url']); ?>" target="_blank" class="inline-flex items-center gap-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white px-4 py-2 rounded-lg font-medium hover:from-[#151d7a] hover:to-[#c2285a] transition-all duration-200 text-sm">
                  Visit Site
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php elseif ($type === 'graphics'): ?>
      <div id="graphicsModal-<?php echo $cat['id']; ?>" class="fixed inset-0 bg-black bg-opacity-75 flex items-start justify-center z-[9999] p-4 overflow-y-auto portfolio-modal" style="display: none;">
        <div class="bg-white rounded-2xl p-6 max-w-6xl w-full mx-4 shadow-2xl relative my-8">
          <button onclick="closeDynamicModal('graphicsModal-<?php echo $cat['id']; ?>')" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors z-10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center"><?php echo $title; ?> Portfolio</h3>
          <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php 
            $galleryImages = array_map('asset_url', array_column($children, 'image'));
            foreach ($children as $idx => $child): ?>
              <div class="space-y-3">
                <div class="flex items-center gap-3 mb-3">
                  <span class="inline-block bg-[#1b2299]/20 text-[#1b2299] px-3 py-1 rounded-full text-xs">Design</span>
                  <h4 class="text-lg font-semibold text-gray-800 line-clamp-1"><?php echo htmlspecialchars((string)$child['title']); ?></h4>
                </div>
                <img src="<?php echo asset_url($child['image']); ?>" alt="<?php echo htmlspecialchars((string)$child['title']); ?>" class="w-full h-48 object-cover rounded-lg shadow-md cursor-pointer hover:scale-105 transition-transform duration-300" onclick='openFullscreenGalleryFromList(<?php echo json_encode($galleryImages); ?>, <?php echo $idx; ?>)' />
                <p class="text-gray-600 text-xs line-clamp-2"><?php echo htmlspecialchars((string)$child['description']); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php elseif ($type === 'music'): ?>
      <div id="musicModal-<?php echo $cat['id']; ?>" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[9999] p-4 portfolio-modal" style="display: none;">
        <div class="bg-white rounded-2xl p-6 max-w-6xl w-full mx-4 shadow-2xl relative">
          <button onclick="closeDynamicModal('musicModal-<?php echo $cat['id']; ?>')" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors z-10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center"><?php echo $title; ?> Showcase</h3>
          <div class="grid md:grid-cols-2 gap-6">
            <?php foreach ($children as $child): 
              $vUrl = (string)($child['external_url'] ?? '');
              $vId = (strpos($vUrl, 'v=') !== false) ? explode('v=', $vUrl)[1] : (strpos($vUrl, 'youtu.be/') !== false ? explode('youtu.be/', $vUrl)[1] : '');
              $vId = explode('&', $vId)[0];
            ?>
              <div class="space-y-3">
                <h4 class="text-lg font-semibold text-gray-800 text-center"><?php echo htmlspecialchars((string)$child['title']); ?></h4>
                <div class="relative w-full" style="padding-bottom: 56.25%;">
                  <iframe class="absolute top-0 left-0 w-full h-full rounded-lg dynamic-iframe" data-src="https://www.youtube.com/embed/<?php echo $vId; ?>" frameborder="0" allowfullscreen></iframe>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>

  <!-- Footer -->
  <footer class="bg-gray-900 text-white">
    <div class="container mx-auto px-4 lg:px-8 py-16 grid lg:grid-cols-4 gap-8">
      <div>
        <div class="flex items-center gap-2 mb-4">
          <img src="<?php echo asset_url('assets/images/jlm.png'); ?>" alt="JLM Logo" class="h-10 w-auto mx-auto mb-4" />
        </div>
        <p class="text-gray-300 mb-6 leading-relaxed">Transforming ideas into creative excellence through innovative media production and personalized service across Nigeria and beyond.</p>
        <div class="flex space-x-4">
          <a href="https://facebook.com/jlukamedia" target="_blank" class="w-10 h-10 bg-gray-800 border border-white/20 shadow-sm rounded-lg flex items-center justify-center hover:scale-110 transition-transform duration-300" aria-label="Facebook">
            <!-- Facebook SVG -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" class="h-6 w-6"><path d="M22.675 0h-21.35C.595 0 0 .592 0 1.326v21.348C0 23.408.595 24 1.325 24h11.495v-9.294H9.691v-3.622h3.129V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.797.143v3.24l-1.918.001c-1.504 0-1.797.715-1.797 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116C23.406 24 24 23.408 24 22.674V1.326C24 .592 23.406 0 22.675 0"/></svg>
          </a>
          <a href="https://instagram.com/jlukamedia" target="_blank" class="w-10 h-10 bg-gray-800 border border-white/20 shadow-sm rounded-lg flex items-center justify-center hover:scale-110 transition-transform duration-300" aria-label="Instagram">
            <!-- Instagram SVG -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" class="h-6 w-6"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.334 3.608 1.308.974.974 1.246 2.241 1.308 3.608.058 1.266.069 1.646.069 4.85s-.012 3.584-.07 4.85c-.062 1.366-.334 2.633-1.308 3.608-.974.974-2.241 1.246-3.608 1.308-1.266.058-1.646.069-4.85.069s-3.584-.012-4.85-.07c-1.366-.062-2.633-.334-3.608-1.308-.974-.974-1.246-2.241-1.308-3.608C2.175 15.647 2.163 15.267 2.163 12s.012-3.584.07-4.85c.062-1.366.334-2.633 1.308-3.608C4.515 2.497 5.782 2.225 7.148 2.163 8.414 2.105 8.794 2.094 12 2.094zm0-2.163C8.741 0 8.332.012 7.052.07 5.771.128 4.635.4 3.661 1.374c-.974.974-1.246 2.241-1.308 3.608C2.175 8.414 2.163 8.794 2.163 12s.012 3.584.07 4.85c.062 1.366.334 2.633 1.308 3.608.974.974 2.241 1.246 3.608 1.308 1.266.058 1.646.069 4.85.069s3.584-.012 4.85-.07c1.366-.062 2.633-.334 3.608-1.308.974-.974 1.246-2.241 1.308-3.608.058-1.266.069-1.646.069-4.85s-.012-3.584-.07-4.85c-.062-1.366-.334-2.633-1.308-3.608-.974-.974-2.241-1.246-3.608-1.308C15.647 2.175 15.267 2.163 12 2.163z"/><path d="M12 5.838A6.162 6.162 0 0 0 5.838 12 6.162 6.162 0 0 0 12 18.162 6.162 6.162 0 0 0 18.162 12 6.162 6.162 0 0 0 12 5.838zm0 10.324A4.162 4.162 0 1 1 16.162 12 4.162 4.162 0 0 1 12 16.162zm6.406-11.845a1.44 1.44 0 1 1-2.88 0 1.44 1.44 0 0 1 2.88 0z"/></svg>
          </a>
          <a href="https://linkedin.com/in/jlukamedia" target="_blank" class="w-10 h-10 bg-gray-800 border border-white/20 shadow-sm rounded-lg flex items-center justify-center hover:scale-110 transition-transform duration-300" aria-label="LinkedIn">
            <!-- LinkedIn SVG -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" class="h-6 w-6"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.761 0 5-2.239 5-5v-14c0-2.761-2.239-5-5-5zm-11 19h-3v-10h3v10zm-1.5-11.268c-.966 0-1.75-.784-1.75-1.75s.784-1.75 1.75-1.75 1.75.784 1.75 1.75-.784 1.75-1.75 1.75zm13.5 11.268h-3v-5.604c0-1.337-.025-3.063-1.868-3.063-1.868 0-2.154 1.459-2.154 2.968v5.699h-3v-10h2.881v1.367h.041c.401-.761 1.381-1.563 2.844-1.563 3.042 0 3.604 2.003 3.604 4.605v5.591zm-7.5-10h-3v-10h3v10z"/></svg>
          </a>
          <a href="https://x.com/jlukamedia" target="_blank" class="w-10 h-10 bg-gray-800 border border-white/20 shadow-sm rounded-lg flex items-center justify-center hover:scale-110 transition-transform duration-300" aria-label="X">
            <!-- X (Twitter) SVG -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" class="h-6 w-6"><path d="M17.53 3H21.5l-7.19 8.21L22.5 21h-6.13l-4.81-5.73L5.5 21H1.5l7.62-8.7L1.5 3h6.13l4.5 5.36L17.53 3zm-2.13 15.5h2.13l-5.62-6.7-2.13 2.45L15.4 18.5zM6.47 5h-2.13l5.62 6.7 2.13-2.45L8.6 5z"/></svg>
          </a>
          <a href="https://wa.me/2348150917741" target="_blank" class="w-10 h-10 bg-gray-800 border border-white/20 shadow-sm rounded-lg flex items-center justify-center hover:scale-110 transition-transform duration-300" aria-label="WhatsApp">
            <!-- WhatsApp SVG -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" class="h-6 w-6"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.031-.967-.273-.099-.471-.148-.67.15-.198.297-.767.967-.94 1.166-.173.198-.347.223-.644.075-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.5-.669-.51-.173-.007-.372-.009-.571-.009-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.099 3.2 5.077 4.363.71.306 1.263.489 1.694.626.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.288.173-1.413-.074-.124-.272-.198-.57-.347zm-5.421 7.617h-.001a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.999-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.455 4.436-9.89 9.893-9.89 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.896 6.994c-.003 5.455-4.438 9.89-9.893 9.89zm8.413-18.304A11.815 11.815 0 0012.05 0C5.495 0 .16 5.336.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.681a11.876 11.876 0 005.683 1.448h.005c6.554 0 11.889-5.336 11.893-11.892a11.82 11.82 0 00-3.48-8.382z"/></svg>
          </a>
        </div>
      </div>
      <div>
        <h3 class="text-lg font-medium mb-6">Our Services</h3>
        <ul class="space-y-3">
          <li><a href="#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Video Production</a></li>
          <li><a href="#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Music Production</a></li>
          <li><a href="#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Livestreaming</a></li>
          <li><a href="#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Motion Graphics</a></li>
          <li><a href="#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Graphics Design</a></li>
          <li><a href="#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Media Consultancy</a></li>
          <li><a href="#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">E-Learning Services</a></li>
          <li><a href="#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Web Design</a></li>
        </ul>
      </div>
      <div>
        <h3 class="text-lg font-medium mb-6">Company</h3>
        <ul class="space-y-3">
          <li><a href="#about" class="text-gray-300 hover:text-[#f7de7a] transition-colors">About Us</a></li>
          <li><a href="#portfolio" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Portfolio</a></li>
          <li><a href="#testimonials" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Testimonials</a></li>
          <li><a href="#contact" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Contact</a></li>
          <li><a href="booking.php" class="text-gray-300 hover:text-[#f7de7a] transition-colors flex items-center gap-2">Book Now</a></li>
        </ul>
        <div class="mt-8 space-y-3">
          <div class="flex items-center space-x-3 text-gray-300"><span>📞</span><span>+2348150917741</span></div>
          <div class="flex items-center space-x-3 text-gray-300"><span>✉️</span><span>support@jlm.com.ng</span></div>
          <div class="flex items-center space-x-3 text-gray-300"><span>📍</span><span>Kaduna, Nigeria</span></div>
        </div>
      </div>
      <div>
        <h3 class="text-lg font-medium mb-6">Stay Updated</h3>
        <p class="text-gray-300 mb-4">Subscribe to our newsletter for the latest updates, creative tips, and exclusive offers.</p>
        <form id="newsletterForm" class="space-y-4">
          <input type="email" id="newsletterEmail" placeholder="Enter your email" required class="bg-gray-800 border-gray-700 text-white placeholder-gray-400 focus:border-[#1b2299] w-full px-4 py-2 rounded-lg" />
          <button type="submit" id="newsletterSubmit" class="w-full bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white px-6 py-3 rounded-lg flex items-center justify-center gap-2 transition-all duration-200 hover:scale-105">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 7.165 6 9.388 6 12v2.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            <span id="newsletterButtonText">Subscribe</span>
          </button>
        </form>
      </div>
    </div>
    <div class="bg-gray-800 py-6">
      <div class="container mx-auto px-4 lg:px-8 flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
        <div class="text-gray-400 text-sm flex items-center gap-4">
          © <?php echo date('Y'); ?> JLM. All rights reserved.
          <a href="admin_dashboard.php" class="flex items-center gap-1 text-gray-500 hover:text-[#e4306d] text-xs underline underline-offset-2 transition-colors" title="Admin Login" style="margin-left:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 18.341A8 8 0 1 0 4.572 18.34M12 15v2m0 4h.01" /></svg>
            Admin
          </a>
        </div>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-400">
          <a href="privacy-policy.php" class="hover:text-[#f7de7a] transition-colors">Privacy Policy</a>
          <a href="terms-of-service.php" class="hover:text-[#f7de7a] transition-colors">Terms of Service</a>
          <a href="cookie-policy.php" class="hover:text-[#f7de7a] transition-colors">Cookie Policy</a>
          <button type="button" onclick="openCookieSettings()" class="hover:text-[#f7de7a] transition-colors bg-transparent border-0 p-0 text-inherit cursor-pointer text-sm font-medium">Cookie Settings</button>
        </div>
      </div>
    </div>
  </footer>

  <!-- Toast Notification Container -->
  <div id="toastContainer" class="fixed top-6 right-6 z-[70] space-y-3 max-w-sm"></div>

  <!-- Newsletter Feedback Modal -->
  <div id="subscriptionFeedbackModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/70 p-4">
    <div class="w-full max-w-md rounded-[28px] bg-white p-6 shadow-2xl">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p id="subscriptionFeedbackEyebrow" class="text-xs font-semibold uppercase tracking-[0.24em] text-[#e4306d]">Newsletter</p>
          <h3 id="subscriptionFeedbackTitle" class="mt-2 text-2xl font-bold text-gray-900">Subscription Update</h3>
        </div>
        <button type="button" onclick="closeSubscriptionFeedbackModal()" class="rounded-full border border-gray-200 p-2 text-gray-500 transition hover:border-gray-300 hover:text-gray-700" aria-label="Close subscription feedback modal">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div id="subscriptionFeedbackIconWrap" class="mt-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-green-100 text-green-600">
        <svg id="subscriptionFeedbackSuccessIcon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <svg id="subscriptionFeedbackErrorIcon" xmlns="http://www.w3.org/2000/svg" class="hidden h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <p id="subscriptionFeedbackMessage" class="mt-5 text-base leading-7 text-gray-600"></p>
      <div class="mt-6 flex justify-end">
        <button type="button" onclick="closeSubscriptionFeedbackModal()" class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-[#1b2299] to-[#e4306d] px-5 py-3 font-semibold text-white transition hover:scale-[1.01]">
          Continue
        </button>
      </div>
    </div>
  </div>



  <!-- Back to Top Button (single instance for the whole page) -->

  <!-- Shared Modals (Global) -->
  
  <!-- Video Modal -->
  <div id="videoModal" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 p-4" style="display: none;">
    <div class="bg-white rounded-2xl p-4 max-w-4xl w-full shadow-2xl relative">
      <button onclick="closeVideoModal()" class="absolute -top-12 right-0 text-white hover:text-gray-300 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
      <h3 id="videoModalTitle" class="text-xl font-bold text-gray-900 mb-4 truncate pr-8">Video Title</h3>
      <div class="relative w-full" style="padding-bottom: 56.25%;">
        <iframe id="videoModalFrame" class="absolute top-0 left-0 w-full h-full rounded-lg" frameborder="0" allowfullscreen></iframe>
      </div>
    </div>
  </div>

  <!-- Channel Modal -->
  <div id="channelModal" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 p-4" style="display: none;">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl text-center relative">
      <button onclick="closeChannelModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
      <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fab fa-youtube text-4xl"></i>
      </div>
      <h3 id="channelModalTitle" class="text-2xl font-bold text-gray-900 mb-2">Channel Name</h3>
      <p class="text-gray-600 mb-8">Visit our YouTube channel for more content.</p>
      <a id="channelModalLink" href="#" target="_blank" class="block w-full py-4 bg-[#ff0000] text-white rounded-xl font-bold hover:bg-[#cc0000] transition-colors shadow-lg shadow-red-200">
        Open YouTube Channel
      </a>
    </div>
  </div>

  <!-- Fullscreen Gallery Modal -->
  <div id="fullscreenGalleryModal" class="fixed inset-0 bg-black bg-opacity-95 flex items-center justify-center z-[60] p-4" style="display: none;">
    <div class="relative w-full h-full flex items-center justify-center">
      <button onclick="closeFullscreenGallery()" class="absolute top-4 right-4 text-white hover:text-gray-300 transition-colors z-10 bg-black bg-opacity-50 rounded-full p-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
      <button onclick="previousImage()" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 transition-colors z-10 bg-black bg-opacity-50 rounded-full p-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </button>
      <button onclick="nextImage()" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 transition-colors z-10 bg-black bg-opacity-50 rounded-full p-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </button>
      <div class="relative w-full h-full flex items-center justify-center">
        <img id="fullscreenImage" src="" alt="Fullscreen Portfolio Image" class="max-w-full max-h-full object-contain" />
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white bg-black bg-opacity-50 rounded-full px-4 py-2 text-sm">
          <span id="imageCounter">1 / 1</span>
        </div>
      </div>
    </div>
  </div>

  <button id="backToTopBtn" class="fixed bottom-8 right-8 z-50 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white p-3 rounded-full shadow-lg hover:scale-110 transition-all duration-300 hidden" aria-label="Back to Top">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
  </button>
  

  <script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Page loaded, initializing...');
      
      // Back to Top Button logic
      const backToTopBtn = document.getElementById('backToTopBtn');
      if (backToTopBtn) {
        window.addEventListener('scroll', function() {
          if (window.scrollY > 300) {
            backToTopBtn.classList.remove('hidden');
          } else {
            backToTopBtn.classList.add('hidden');
          }
        });
        backToTopBtn.addEventListener('click', function() {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
      }

      // Section Fade-In on Scroll
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('section-visible');
            obs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.15 });
      document.querySelectorAll('section').forEach(section => {
        section.classList.add('section-fade');
        observer.observe(section);
      });

      // Count up animation for stats
      function animateCountUp(el, target, suffix = '+') {
        let start = 0;
        const duration = 1200;
        const startTime = performance.now();
        function update(now) {
          const progress = Math.min((now - startTime) / duration, 1);
          const value = Math.floor(progress * target);
          el.textContent = value + suffix;
          if (progress < 1) {
            requestAnimationFrame(update);
          } else {
            el.textContent = target + suffix;
          }
        }
        requestAnimationFrame(update);
      }
      document.querySelectorAll('.countup').forEach(el => {
        const target = parseInt(el.getAttribute('data-count'), 10);
        let suffix = el.textContent.replace(/\d+/g, '');
        if (el.textContent.includes('/')) suffix = '/7';
        animateCountUp(el, target, suffix);
      });

      // Button Ripple Effect
      function createRipple(event) {
        const button = event.currentTarget;
        const circle = document.createElement('span');
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;
        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
        circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
        circle.classList.add('ripple-effect');
        const ripple = button.getElementsByClassName('ripple-effect')[0];
        if (ripple) {
          ripple.remove();
        }
        button.appendChild(circle);
      }
      document.querySelectorAll('.ripple').forEach(btn => {
        btn.addEventListener('click', createRipple);
      });

      // Check modals
      const videoModal = document.getElementById('videoModal');
      const channelModal = document.getElementById('channelModal');
      
      if (videoModal) {
        console.log('Video modal found');
      } else {
        console.error('Video modal not found');
      }
      
      if (channelModal) {
        console.log('Channel modal found');
      } else {
        console.error('Channel modal not found');
      }

      // Check for contact form messages and show toast notifications
      <?php if ($contact_success): ?>
      showToast('Thank you for your message! We have received your inquiry and will respond within 24 hours. A confirmation email has been sent to your inbox.', 'success');
      <?php endif; ?>
      
      <?php if ($contact_error): ?>
      showToast('We encountered an issue processing your message. Please try again or contact us directly via WhatsApp.', 'error');
      <?php endif; ?>
    });

    // Toast notification functions
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

    function showSubscriptionFeedbackModal(title, message, type = 'success') {
      const modal = document.getElementById('subscriptionFeedbackModal');
      const titleEl = document.getElementById('subscriptionFeedbackTitle');
      const messageEl = document.getElementById('subscriptionFeedbackMessage');
      const eyebrowEl = document.getElementById('subscriptionFeedbackEyebrow');
      const wrapEl = document.getElementById('subscriptionFeedbackIconWrap');
      const successIcon = document.getElementById('subscriptionFeedbackSuccessIcon');
      const errorIcon = document.getElementById('subscriptionFeedbackErrorIcon');

      if (!modal || !titleEl || !messageEl || !eyebrowEl || !wrapEl || !successIcon || !errorIcon) {
        showToast(message, type === 'success' ? 'success' : 'error');
        return;
      }

      titleEl.textContent = title || 'Subscription Update';
      messageEl.textContent = message || '';
      eyebrowEl.textContent = type === 'success' ? 'Newsletter' : 'Attention';

      wrapEl.className = type === 'success'
        ? 'mt-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-green-100 text-green-600'
        : 'mt-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-red-100 text-red-600';

      successIcon.classList.toggle('hidden', type !== 'success');
      errorIcon.classList.toggle('hidden', type === 'success');

      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function closeSubscriptionFeedbackModal() {
      const modal = document.getElementById('subscriptionFeedbackModal');
      if (!modal) return;
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    // Portfolio modal functions
    function openVideoModal(videoUrl, title) {
      console.log('Opening video modal:', videoUrl, title);
      const modal = document.getElementById('videoModal');
      const titleElement = document.getElementById('videoModalTitle');
      const iframe = document.getElementById('videoModalFrame');
      
      if (!modal || !titleElement || !iframe) {
        console.error('Modal elements not found');
        return;
      }
      
      // Convert YouTube URL to embed format
      const videoId = videoUrl.split('v=')[1] || videoUrl.split('youtu.be/')[1];
      const embedUrl = `https://www.youtube.com/embed/${videoId}`;
      
      titleElement.textContent = title;
      iframe.src = embedUrl;
      modal.style.display = 'flex';
      modal.classList.remove('hidden');
      
      // Prevent body scroll
      document.body.style.overflow = 'hidden';
    }

    function closeVideoModal() {
      const modal = document.getElementById('videoModal');
      const iframe = document.getElementById('videoModalFrame');
      
      if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
      }
      if (iframe) {
        iframe.src = ''; // Stop video playback
      }
      
      // Restore body scroll
      document.body.style.overflow = 'auto';
    }

    function openChannelModal(channelUrl, title) {
      console.log('Opening channel modal:', channelUrl, title);
      const modal = document.getElementById('channelModal');
      const titleElement = document.getElementById('channelModalTitle');
      const linkElement = document.getElementById('channelModalLink');
      
      if (!modal || !titleElement || !linkElement) {
        console.error('Channel modal elements not found');
        return;
      }
      
      titleElement.textContent = title;
      linkElement.href = channelUrl;
      modal.style.display = 'flex';
      modal.classList.remove('hidden');
      
      // Prevent body scroll
      document.body.style.overflow = 'hidden';
    }

    function closeChannelModal() {
      const modal = document.getElementById('channelModal');
      
      if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
      }
      
      // Restore body scroll
      document.body.style.overflow = 'auto';
    }

    function openDynamicModal(modalId) {
      console.log('Opening dynamic modal:', modalId);
      const modal = document.getElementById(modalId);
      if (!modal) return;
      
      modal.style.display = 'flex';
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      
      // Start any videos in the modal
      modal.querySelectorAll('.dynamic-iframe').forEach(iframe => {
        if (iframe.dataset.src) {
          iframe.src = iframe.dataset.src;
        }
      });
    }

    function closeDynamicModal(modalId) {
      const modal = document.getElementById(modalId);
      if (!modal) return;
      
      modal.style.display = 'none';
      modal.classList.add('hidden');
      document.body.style.overflow = 'auto';
      
      // Stop any videos in the modal
      modal.querySelectorAll('.dynamic-iframe').forEach(iframe => {
        iframe.src = '';
      });
    }

    // Keep legacy modal functions for backward compatibility or simple one-off modals
    function openVideoModal(videoUrl, title) {
      const modal = document.getElementById('videoModal');
      const iframe = document.getElementById('videoModalFrame');
      const titleElement = document.getElementById('videoModalTitle');
      if (!modal || !iframe) return;
      
      const videoId = videoUrl.split('v=')[1] || videoUrl.split('youtu.be/')[1];
      const embedUrl = `https://www.youtube.com/embed/${videoId.split('&')[0]}`;
      
      if (titleElement) titleElement.textContent = title;
      iframe.src = embedUrl;
      modal.style.display = 'flex';
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeVideoModal() {
      const modal = document.getElementById('videoModal');
      const iframe = document.getElementById('videoModalFrame');
      if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
      }
      if (iframe) iframe.src = '';
      document.body.style.overflow = 'auto';
    }

    // Fullscreen Gallery Variables
    let currentImageIndex = 0;
    let portfolioImages = [];

    function openFullscreenGalleryFromList(images, index) {
      portfolioImages = images;
      openFullscreenGallery(index);
    }

    function openFullscreenGallery(index) {
      currentImageIndex = index;
      const modal = document.getElementById('fullscreenGalleryModal');
      const image = document.getElementById('fullscreenImage');
      const counter = document.getElementById('imageCounter');
      
      if (!modal || !image || !counter) return;
      
      image.src = portfolioImages[currentImageIndex];
      counter.textContent = `${currentImageIndex + 1} / ${portfolioImages.length}`;
      modal.style.display = 'flex';
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeFullscreenGallery() {
      const modal = document.getElementById('fullscreenGalleryModal');
      if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
      }
      document.body.style.overflow = 'auto';
    }

    function nextImage() {
      currentImageIndex = (currentImageIndex + 1) % portfolioImages.length;
      updateFullscreenImage();
    }

    function previousImage() {
      currentImageIndex = (currentImageIndex - 1 + portfolioImages.length) % portfolioImages.length;
      updateFullscreenImage();
    }

    function updateFullscreenImage() {
      const image = document.getElementById('fullscreenImage');
      const counter = document.getElementById('imageCounter');
      
      if (image && counter) {
        image.src = portfolioImages[currentImageIndex];
        counter.textContent = `${currentImageIndex + 1} / ${portfolioImages.length}`;
      }
    }

    // Test function for debugging
    function testModals() {
      console.log('Testing modals...');
      openVideoModal('https://youtu.be/j2fTIrBSWUI', 'Test Video');
    }

    // Utility functions
    function scrollToSection(e, href) {
      e.preventDefault();
      const el = document.querySelector(href);
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(event) {
      if (event.target.classList.contains('portfolio-modal')) {
        closeDynamicModal(event.target.id);
      }
      
      const videoModal = document.getElementById('videoModal');
      const channelModal = document.getElementById('channelModal');
      const fullscreenGalleryModal = document.getElementById('fullscreenGalleryModal');
      
      if (videoModal && event.target === videoModal) closeVideoModal();
      if (channelModal && event.target === channelModal) closeChannelModal();
      if (fullscreenGalleryModal && event.target === fullscreenGalleryModal) closeFullscreenGallery();
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        document.querySelectorAll('.portfolio-modal').forEach(m => {
          if (!m.classList.contains('hidden')) closeDynamicModal(m.id);
        });
        closeVideoModal();
        closeChannelModal();
        closeFullscreenGallery();
      }
      
      // Keyboard navigation for fullscreen gallery
      const fullscreenGalleryModal = document.getElementById('fullscreenGalleryModal');
      if (fullscreenGalleryModal && fullscreenGalleryModal.style.display !== 'none') {
        if (event.key === 'ArrowRight') {
          event.preventDefault();
          nextImage();
        } else if (event.key === 'ArrowLeft') {
          event.preventDefault();
          previousImage();
        }
      }
    });

    // Testimonial Slider Functionality
    document.addEventListener('DOMContentLoaded', function() {
      const testimonialTrack = document.getElementById('testimonial-track');
      const prevBtn = document.getElementById('prev-btn');
      const nextBtn = document.getElementById('next-btn');
      
      if (testimonialTrack && prevBtn && nextBtn) {
        let currentIndex = 0;
        const testimonials = testimonialTrack.children;
        const totalTestimonials = testimonials.length;
        
        function updateSlider() {
          const translateX = -currentIndex * 100;
          testimonialTrack.style.transform = `translateX(${translateX}%)`;
          
          // Update button states
          prevBtn.disabled = currentIndex === 0;
          nextBtn.disabled = currentIndex === totalTestimonials - 1;
          
          // Visual feedback for disabled buttons
          if (prevBtn.disabled) {
            prevBtn.style.opacity = '0.5';
            prevBtn.style.cursor = 'not-allowed';
          } else {
            prevBtn.style.opacity = '1';
            prevBtn.style.cursor = 'pointer';
          }
          
          if (nextBtn.disabled) {
            nextBtn.style.opacity = '0.5';
            nextBtn.style.cursor = 'not-allowed';
          } else {
            nextBtn.style.opacity = '1';
            nextBtn.style.cursor = 'pointer';
          }
        }
        
        // Next button click
        nextBtn.addEventListener('click', function() {
          if (currentIndex < totalTestimonials - 1) {
            currentIndex++;
            updateSlider();
          }
        });
        
        // Previous button click
        prevBtn.addEventListener('click', function() {
          if (currentIndex > 0) {
            currentIndex--;
            updateSlider();
          }
        });
        
        // Auto-scroll functionality (optional)
        let autoScrollInterval;
        
        function startAutoScroll() {
          autoScrollInterval = setInterval(function() {
            if (currentIndex < totalTestimonials - 1) {
              currentIndex++;
            } else {
              currentIndex = 0; // Loop back to first
            }
            updateSlider();
          }, 5000); // Change testimonial every 5 seconds
        }
        
        function stopAutoScroll() {
          if (autoScrollInterval) {
            clearInterval(autoScrollInterval);
          }
        }
        
        // Start auto-scroll
        startAutoScroll();
        
        // Pause auto-scroll on hover
        testimonialTrack.addEventListener('mouseenter', stopAutoScroll);
        testimonialTrack.addEventListener('mouseleave', startAutoScroll);
        
        // Initialize slider
        updateSlider();
      }
    });

    // Newsletter Subscription
    document.addEventListener('DOMContentLoaded', function() {
      const newsletterForm = document.getElementById('newsletterForm');
      const newsletterEmail = document.getElementById('newsletterEmail');
      const newsletterSubmit = document.getElementById('newsletterSubmit');
      const newsletterButtonText = document.getElementById('newsletterButtonText');

      if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          const email = newsletterEmail.value.trim();
          
          if (!email) {
            showToast('Please enter your email address.', 'error');
            return;
          }
          
          // Show loading state
          newsletterSubmit.disabled = true;
          newsletterButtonText.textContent = 'Subscribing...';
          
          // Send subscription request
          fetch('subscribe.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email)
          })
          .then(async (response) => {
            const data = await response.json();
            return { response, data };
          })
          .then(({ response, data }) => {
            showSubscriptionFeedbackModal(
              data.title || (data.success ? 'Subscription Update' : 'Subscription Problem'),
              data.message || 'Unable to complete your request right now.',
              data.success ? 'success' : 'error'
            );

            if (response.ok && data.success) {
              newsletterEmail.value = '';
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showSubscriptionFeedbackModal('Unable To Subscribe', 'An error occurred. Please try again.', 'error');
          })
          .finally(() => {
            // Reset button state
            newsletterSubmit.disabled = false;
            newsletterButtonText.textContent = 'Subscribe';
          });
        });
      }
    });
  </script>
  <?php require_once __DIR__ . '/includes/cookie_consent.php'; ?>
</body>
</html> 
