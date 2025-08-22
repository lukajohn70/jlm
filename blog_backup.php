<?php
// Error reporting for development only
if (($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once __DIR__ . '/config/config.php';

try {
    $stmt = $pdo->query("SELECT blog_posts.*, users.name AS author FROM blog_posts JOIN users ON blog_posts.author_id = users.id WHERE status = 'published' ORDER BY created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Blog query error: " . $e->getMessage());
    $posts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blog - JLM Creative Media</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Read our latest blog posts about creative services, design tips, and industry insights.">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
    </style>
</head>
<body class="bg-white">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-lg border-b border-white/30 sticky top-0 z-40">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="flex items-center justify-between py-4">
        <div>
          <a href="index.php#home" class="flex items-center gap-2" aria-label="Go to home section">
            <img src="assets/images/jlm.png" alt="JLM logo, home" class="h-10 w-auto" />
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
          <a href="blog.php" class="text-[#1b2299] relative group">
            Blog
            <span class="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-[#1b2299] to-[#e4306d]"></span>
          </a>
          <a href="index.php#contact" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 relative group">
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
          <a href="index.php#contact" class="border-2 border-[#1b2299] text-[#1b2299] px-6 py-2 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-[#1b2299] hover:text-white flex items-center gap-2">
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
      <div id="mobile-menu" class="md:hidden absolute top-16 left-0 right-0 bg-white backdrop-blur-lg border border-white/30 shadow-lg rounded-xl hidden z-50">
        <nav class="flex flex-col space-y-4 p-4">
          <a href="index.php#home" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Home</a>
          <a href="index.php#services" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Services</a>
          <a href="index.php#portfolio" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Portfolio</a>
          <a href="index.php#about" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">About</a>
          <a href="index.php#testimonials" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Testimonials</a>
          <a href="blog.php" class="text-[#1b2299] py-2">Blog</a>
          <a href="index.php#contact" class="text-gray-700 hover:text-[#1b2299] transition-colors duration-200 py-2">Contact</a>
          <div class="space-y-2 mt-4">
            <a href="booking.php" class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white w-full block text-center py-2 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:from-[#151d7a] hover:to-[#c2285a]">Book Now</a>
            <a href="index.php#contact" class="border-2 border-[#1b2299] text-[#1b2299] w-full block text-center py-2 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-[#1b2299] hover:text-white">Contact</a>
          </div>
        </nav>
      </div>
    </div>
  </header>

  <main class="transition-all duration-500 opacity-100 fade-in-on-load">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Our Blog</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Discover insights, tips, and stories from our creative journey. Stay updated with the latest trends in design and digital marketing.
            </p>
        </div>

        <!-- Blog Posts Grid -->
        <?php if (!empty($posts)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($posts as $post): ?>
            <article class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <?php if ($post['image']): ?>
                <div class="aspect-w-16 aspect-h-9">
                    <img src="<?php echo htmlspecialchars($post['image']); ?>" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                         class="w-full h-48 object-cover">
                </div>
                <?php endif; ?>
                
                <div class="p-6">
                    <div class="flex items-center text-sm text-gray-500 mb-3">
                        <i class="fas fa-user mr-2"></i>
                        <span><?php echo htmlspecialchars($post['author']); ?></span>
                        <span class="mx-2">•</span>
                        <i class="fas fa-calendar mr-2"></i>
                        <span><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                    </div>
                    
                    <h2 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2">
                        <a href="blog_post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                       class="hover:text-[#1b2299] transition-colors">
                        <?php echo htmlspecialchars($post['title']); ?>
                    </a>
                    </h2>
                    
                    <p class="text-gray-600 mb-4 line-clamp-3">
                        <?php 
                        $excerpt = strip_tags($post['content']);
                        echo htmlspecialchars(strlen($excerpt) > 150 ? substr($excerpt, 0, 150) . '...' : $excerpt); 
                        ?>
                    </p>
                    
                    <a href="blog_post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                       class="inline-flex items-center text-[#1b2299] font-semibold hover:text-[#e4306d] transition-colors">
                        Read More
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="max-w-md mx-auto">
                <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-newspaper text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Blog Posts Yet</h3>
                <p class="text-gray-600 mb-6">
                    We're working on creating amazing content for you. Check back soon for our latest blog posts!
                </p>
                <a href="index.php" 
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-lg font-semibold hover:from-[#151d7a] hover:to-[#c2285a] transition">
                    <i class="fas fa-home mr-2"></i>
                    Back to Home
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-900 text-white">
    <div class="container mx-auto px-4 lg:px-8 py-16 grid lg:grid-cols-4 gap-8">
      <div>
        <div class="flex items-center gap-2 mb-4">
          <img src="assets/images/jlm.png" alt="JLM Logo" class="h-10 w-auto mx-auto mb-4" />
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
          <li><a href="index.php#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Video Production</a></li>
          <li><a href="index.php#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Music Production</a></li>
          <li><a href="index.php#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Livestreaming</a></li>
          <li><a href="index.php#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Motion Graphics</a></li>
          <li><a href="index.php#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Graphics Design</a></li>
          <li><a href="index.php#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Media Consultancy</a></li>
          <li><a href="index.php#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">E-Learning Services</a></li>
          <li><a href="index.php#services" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Web Design</a></li>
        </ul>
      </div>
      <div>
        <h3 class="text-lg font-medium mb-6">Company</h3>
        <ul class="space-y-3">
          <li><a href="index.php#about" class="text-gray-300 hover:text-[#f7de7a] transition-colors">About Us</a></li>
          <li><a href="index.php#portfolio" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Portfolio</a></li>
          <li><a href="index.php#testimonials" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Testimonials</a></li>
          <li><a href="index.php#contact" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Contact</a></li>
          <li><a href="booking.php" class="text-gray-300 hover:text-[#f7de7a] transition-colors flex items-center gap-2">Book Now</a></li>
          <li><a href="blog.php" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Blog</a></li>
          <li><a href="ticket_lookup.php" class="text-gray-300 hover:text-[#f7de7a] transition-colors">Track Ticket</a></li>
        </ul>
        <div class="mt-8 space-y-3">
          <div class="flex items-center space-x-3 text-gray-300"><span>📞</span><span>+2348150917741</span></div>
          <div class="flex items-center space-x-3 text-gray-300"><span>✉️</span><span>info@jlm.com.ng</span></div>
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
        <div class="flex space-x-6 text-sm text-gray-400">
          <a href="privacy-policy.php" class="hover:text-[#f7de7a] transition-colors">Privacy Policy</a>
          <a href="terms-of-service.php" class="hover:text-[#f7de7a] transition-colors">Terms of Service</a>
          <a href="cookie-policy.php" class="hover:text-[#f7de7a] transition-colors">Cookie Policy</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Back to Top Button -->
  <button id="backToTop" class="fixed bottom-8 right-8 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition-all duration-300 opacity-0 invisible z-50">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
    </svg>
  </button>

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

    // Back to top button
    const backToTopButton = document.getElementById('backToTop');
    if (backToTopButton) {
      window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
          backToTopButton.classList.remove('opacity-0', 'invisible');
          backToTopButton.classList.add('opacity-100', 'visible');
        } else {
          backToTopButton.classList.add('opacity-0', 'invisible');
          backToTopButton.classList.remove('opacity-100', 'visible');
        }
      });

      backToTopButton.addEventListener('click', function() {
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });
    }

    // Add line-clamp utility if not available
    if (!document.querySelector('.line-clamp-2')) {
      const style = document.createElement('style');
      style.textContent = `
        .line-clamp-2 {
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
          overflow: hidden;
        }
        .line-clamp-3 {
          display: -webkit-box;
          -webkit-line-clamp: 3;
          -webkit-box-orient: vertical;
          overflow: hidden;
        }
      `;
      document.head.appendChild(style);
    }
  </script>
</body>
</html> 