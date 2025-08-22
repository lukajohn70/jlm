<?php
// JLM Freelancer Web App - Landing Page
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'JoGigs - Freelancer Platform';
$page_description = 'Join JoGigs and connect with clients for creative projects. Find opportunities in video production, design, branding, and more.';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
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
        .fade-in-on-load {
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .card-shadow {
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }
            
            .min-h-screen {
                min-height: 100vh;
                padding: 2rem 0;
            }
            
            .text-4xl.md\\:text-5xl.lg\\:text-6xl.font-bold {
                font-size: 2.5rem;
                line-height: 1.2;
            }
            
            .text-xl {
                font-size: 1.125rem;
            }
            
            .container.mx-auto.px-4.lg\\:px-8 {
                padding: 0 1rem;
            }
            
            .flex.flex-col.sm\\:flex-row.gap-4 {
                flex-direction: column;
                gap: 1rem;
            }
            
            .grid.lg\\:grid-cols-2.gap-12 {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .text-center.lg\\:text-left {
                text-align: center;
            }
            
            .justify-center.lg\\:justify-start {
                justify-content: center;
            }
            
            .hidden.md\\:flex {
                display: none;
            }
            
            .flex.items-center.space-x-4 {
                flex-direction: column;
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .min-h-screen {
                min-height: 100vh;
                padding: 1rem 0;
            }
            
            .text-4xl.md\\:text-5xl.lg\\:text-6xl.font-bold {
                font-size: 2rem;
            }
            
            .text-xl {
                font-size: 1rem;
            }
            
            .w-8.h-8 {
                width: 2rem;
                height: 2rem;
            }
            
            .text-xl.font-bold {
                font-size: 1.125rem;
            }
            
            .h-16 {
                height: 4rem;
            }
            
            .py-20 {
                padding-top: 3rem;
                padding-bottom: 3rem;
            }
        }
    </style>
</head>
<body class="bg-white fade-in-on-load">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white/60 backdrop-blur-md border-b border-white/30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="landing.php" class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-bold">JLM</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">JoGigs</span>
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="#overview" class="text-gray-700 hover:text-[#667eea] transition-colors duration-200">Overview</a>
                    <a href="#features" class="text-gray-700 hover:text-[#667eea] transition-colors duration-200">Features</a>
                    <a href="#how-it-works" class="text-gray-700 hover:text-[#667eea] transition-colors duration-200">How It Works</a>
                    <a href="#expectations" class="text-gray-700 hover:text-[#667eea] transition-colors duration-200">Expectations</a>
                    <a href="#benefits" class="text-gray-700 hover:text-[#667eea] transition-colors duration-200">Benefits</a>
                    <a href="#contact" class="text-gray-700 hover:text-[#667eea] transition-colors duration-200">Contact</a>
                </nav>
                
                <!-- Desktop CTA Buttons -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="login.php" class="text-gray-700 hover:text-[#667eea] transition-colors duration-200">Sign In</a>
                    <a href="register.php" class="btn-primary text-white px-6 py-2 rounded-lg font-semibold">
                        Join Now
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg id="mobile-menu-icon-open" class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg id="mobile-menu-icon-close" class="w-6 h-6 text-gray-700 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="md:hidden absolute top-16 left-0 right-0 bg-white/95 backdrop-blur-lg border-b border-gray-200 shadow-lg hidden z-50">
                <nav class="flex flex-col space-y-1 p-4">
                    <a href="#overview" class="block px-4 py-3 text-gray-700 hover:text-[#667eea] hover:bg-gray-50 rounded-lg transition-colors duration-200">Overview</a>
                    <a href="#features" class="block px-4 py-3 text-gray-700 hover:text-[#667eea] hover:bg-gray-50 rounded-lg transition-colors duration-200">Features</a>
                    <a href="#how-it-works" class="block px-4 py-3 text-gray-700 hover:text-[#667eea] hover:bg-gray-50 rounded-lg transition-colors duration-200">How It Works</a>
                    <a href="#expectations" class="block px-4 py-3 text-gray-700 hover:text-[#667eea] hover:bg-gray-50 rounded-lg transition-colors duration-200">Expectations</a>
                    <a href="#benefits" class="block px-4 py-3 text-gray-700 hover:text-[#667eea] hover:bg-gray-50 rounded-lg transition-colors duration-200">Benefits</a>
                    <a href="#contact" class="block px-4 py-3 text-gray-700 hover:text-[#667eea] hover:bg-gray-50 rounded-lg transition-colors duration-200">Contact</a>
                    <div class="border-t border-gray-200 pt-4 mt-4 space-y-2">
                        <a href="login.php" class="block px-4 py-3 text-gray-700 hover:text-[#667eea] hover:bg-gray-50 rounded-lg transition-colors duration-200">Sign In</a>
                        <a href="register.php" class="block px-4 py-3 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg font-semibold text-center hover:from-[#5a67d8] hover:to-[#6b46c1] transition-all duration-200">
                            Join Now
                        </a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-50 to-white">
      <div class="absolute inset-0 bg-gradient-to-r from-[#667eea]/5 to-[#764ba2]/5 pointer-events-none"></div>
      <div class="absolute top-20 left-10 w-32 h-32 bg-[#667eea]/20 rounded-full blur-xl animate-pulse pointer-events-none"></div>
      <div class="absolute bottom-20 right-10 w-48 h-48 bg-[#764ba2]/20 rounded-full blur-xl animate-pulse pointer-events-none"></div>
      <div class="container mx-auto px-4 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div class="text-center lg:text-left">
            <div class="inline-flex items-center space-x-2 bg-white/80 backdrop-blur-sm border border-[#667eea]/20 rounded-full px-4 py-2 mb-6">
              <span class="text-sm font-medium text-gray-700">Nigeria's Premier Freelancing Platform</span>
            </div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
              Your Gateway to <span class="bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent">Creative Success</span>
            </h1>
            <p class="text-xl text-gray-600 mb-8 max-w-2xl">
              Join JoGigs - Nigeria's premier freelancing platform for creative professionals. Connect with quality clients, showcase your talent, and build a thriving freelance career with secure payments and professional support.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
              <a href="register.php" class="bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white px-6 py-3 rounded-lg shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:from-[#5a67d8] hover:to-[#6b46c1] flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Start Earning Today
              </a>
              <a href="#how-it-works" class="border-2 border-[#667eea] text-[#667eea] px-8 py-3 text-lg rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-[#667eea] hover:text-white flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-4.03 9-9 9S3 17 3 12 7.03 3 12 3s9 4.03 9 9z" /></svg>
                Learn More
              </a>
            </div>
          </div>
          <div class="relative">
            <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&h=600&fit=crop&crop=faces" alt="Creative freelancers working" class="w-full h-[500px] object-cover rounded-2xl shadow-2xl" />
            <div class="absolute inset-0 bg-gradient-to-tr from-[#667eea]/20 to-[#764ba2]/20 rounded-2xl pointer-events-none"></div>
            <div class="absolute top-2 left-2 sm:-top-6 sm:-left-8 bg-white/60 backdrop-blur-md border border-white/30 p-4 rounded-xl shadow-lg animate-pulse">
              <span class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-green-300 animate-pulse"></span>
                <span class="text-sm font-medium">Active</span>
              </span>
            </div>
            <div class="absolute bottom-4 right-4 bg-white/80 backdrop-blur-md border border-white/30 p-2 rounded-lg shadow-lg animate-pulse
                text-xs font-semibold min-w-[70px] flex flex-col items-center justify-center z-20">
              <div class="font-medium">80%</div>
              <div class="text-gray-500" style="font-size:10px;">Earnings</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Platform Overview Section -->
    <section id="overview" class="py-20 bg-white section-fade">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">What is JoGigs?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    JoGigs is Nigeria's premier freelancing platform, connecting creative professionals with quality clients 
                    in video production, design, branding, and digital services.
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Platform Overview</h3>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">Quality-Focused</h4>
                                <p class="text-gray-600">We prioritize quality over quantity. Every project is carefully vetted to ensure it meets our high standards.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">Professional Network</h4>
                                <p class="text-gray-600">Join a community of verified creative professionals and reputable clients in the Nigerian market.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">Secure & Reliable</h4>
                                <p class="text-gray-600">Our platform ensures secure payments, clear communication, and reliable project management.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">What You Can Expect</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                            <span class="text-gray-700">Regular project opportunities in your field</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                            <span class="text-gray-700">Fair compensation (80% of project value)</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-purple-500 rounded-full mr-3"></div>
                            <span class="text-gray-700">Professional client relationships</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full mr-3"></div>
                            <span class="text-gray-700">Clear project requirements and deadlines</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-red-500 rounded-full mr-3"></div>
                            <span class="text-gray-700">Support and guidance throughout projects</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></div>
                            <span class="text-gray-700">Opportunity to build your portfolio</span>
                        </div>
                    </div>
                    
                    <div class="mt-8 p-4 bg-white rounded-lg border border-gray-200">
                        <h4 class="font-semibold text-gray-900 mb-2">Platform Fee Structure</h4>
                        <p class="text-sm text-gray-600">
                            <strong>Freelancer Earnings:</strong> 80% of project value<br>
                            <strong>Platform Fee:</strong> 20% (covers payment processing, support, and platform maintenance)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50 section-fade">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Why Choose JoGigs?</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    We provide everything you need to succeed as a freelancer in the creative industry, 
                    with clear expectations and professional support.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg card-shadow p-8 text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Quality Projects</h3>
                    <p class="text-gray-600">
                        Access to high-quality projects from reputable clients in video production, design, branding, and more. 
                        We vet all clients to ensure professional relationships.
                    </p>
                </div>
                
                <div class="bg-white rounded-lg card-shadow p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Secure Payments</h3>
                    <p class="text-gray-600">
                        Get paid securely and on time. We handle all payment processing so you can focus on your work. 
                        Earn 80% of project value with transparent fee structure.
                    </p>
                </div>
                
                <div class="bg-white rounded-lg card-shadow p-8 text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Professional Support</h3>
                    <p class="text-gray-600">
                        Get professional support and guidance throughout your freelance journey. 
                        Clear communication channels and dispute resolution.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-20 bg-white section-fade">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">How It Works</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Get started in just a few simple steps
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-white text-2xl font-bold">1</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Create Profile</h3>
                    <p class="text-gray-600">
                        Sign up and create your professional profile showcasing your skills and portfolio.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-white text-2xl font-bold">2</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Browse Jobs</h3>
                    <p class="text-gray-600">
                        Explore available projects that match your skills and interests.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-white text-2xl font-bold">3</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Submit Proposals</h3>
                    <p class="text-gray-600">
                        Submit compelling proposals and bid on projects that interest you.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-white text-2xl font-bold">4</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Get Paid</h3>
                    <p class="text-gray-600">
                        Complete projects and receive secure payments through our platform.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Freelancer Expectations Section -->
    <section id="expectations" class="py-20 bg-white section-fade">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">What We Expect From You</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    To maintain our high standards and ensure success for everyone, we have clear expectations for our freelancers
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-8 border-l-4 border-blue-500">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Professional Commitment</h3>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Meet project deadlines consistently</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Maintain high quality standards</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Communicate clearly and promptly</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-8 border-l-4 border-green-500">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Reliability & Accountability</h3>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Honor your commitments once accepted</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Provide regular project updates</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Be transparent about challenges</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-8 border-l-4 border-purple-500">
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Skill & Portfolio</h3>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-purple-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Maintain an updated portfolio</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-purple-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Only accept projects within your expertise</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-purple-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Continuously improve your skills</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-12 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-semibold text-yellow-800 mb-2">Important Note</h3>
                        <p class="text-yellow-700">
                            <strong>Penalty System:</strong> We have a fair penalty system to maintain quality. Backing out after accepting a job may result in a 20% penalty of the project value. 
                            This ensures reliability and protects our clients while maintaining a professional environment for all freelancers.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" class="py-20 bg-gray-50 section-fade">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Benefits for Freelancers</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Everything you need to succeed in the creative industry
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white rounded-lg card-shadow p-8">
                    <h3 class="text-2xl font-semibold text-gray-900 mb-4">Flexible Work Schedule</h3>
                    <p class="text-gray-600 mb-4">
                        Work on your own terms. Choose projects that fit your schedule and lifestyle.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Work from anywhere
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Choose your projects
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Set your own rates
                        </li>
                    </ul>
                </div>
                
                <div class="bg-white rounded-lg card-shadow p-8">
                    <h3 class="text-2xl font-semibold text-gray-900 mb-4">Professional Growth</h3>
                    <p class="text-gray-600 mb-4">
                        Build your portfolio and expand your network with every project.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Build your portfolio
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Expand your network
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Learn new skills
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white section-fade">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold mb-4">Ready to Start Your Freelancing Journey?</h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto">
                Join thousands of creative professionals who are already earning on JoGigs.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="register.php" class="bg-white text-[#667eea] px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-colors duration-200">
                    Create Your Account
                </a>
                <a href="index.php" class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-[#667eea] transition-colors duration-200">
                    Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-bold">JLM</span>
                        </div>
                        <span class="text-xl font-bold">JoGigs</span>
                    </div>
                    <p class="text-gray-300 mb-4 max-w-md">
                        The premier platform for creative professionals to connect with clients and grow their careers.
                    </p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-300 hover:text-white transition-colors duration-200">Features</a></li>
                        <li><a href="#how-it-works" class="text-gray-300 hover:text-white transition-colors duration-200">How It Works</a></li>
                        <li><a href="#benefits" class="text-gray-300 hover:text-white transition-colors duration-200">Benefits</a></li>
                        <li><a href="register.php" class="text-gray-300 hover:text-white transition-colors duration-200">Join Now</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact</h3>
                    <div class="space-y-2 text-gray-300">
                        <p>📍 Lagos, Nigeria</p>
                        <p>📧 info@jogigs.com</p>
                        <p>📞 +234 XXX XXX XXXX</p>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">
                    © <?php echo date('Y'); ?> JoGigs. All rights reserved. | 
                    <a href="terms.php" class="text-gray-400 hover:text-white transition-colors duration-200">Terms of Service</a> | 
                    <a href="privacy.php" class="text-gray-400 hover:text-white transition-colors duration-200">Privacy Policy</a>
                    <span class="mx-2">|</span>
                    <a href="admin_login.php" class="text-gray-500 hover:text-gray-300 transition-colors duration-200 text-xs" title="Admin Access">Admin</a>
                </p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('section-visible');
                }
            });
        }, observerOptions);

        // Observe all sections with fade class
        document.querySelectorAll('.section-fade').forEach(section => {
            observer.observe(section);
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Mobile Menu Toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenuIconOpen = document.getElementById('mobile-menu-icon-open');
        const mobileMenuIconClose = document.getElementById('mobile-menu-icon-close');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            mobileMenuIconOpen.classList.toggle('hidden');
            mobileMenuIconClose.classList.toggle('hidden');
        });
    </script>
</body>
</html>
