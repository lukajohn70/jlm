<?php
/**
 * JLM 404 Not Found Page
 */

declare(strict_types=1);

// Send 404 status header
http_response_code(404);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/blog_helpers.php';

$page_title = '404 - Page Not Found';
$page_description = 'The page you are looking for does not exist or has been moved.';
$current_page = '404';

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .glow-blob {
        filter: blur(120px);
        opacity: 0.15;
        animation: floatBlob 10s ease-in-out infinite alternate;
    }
    @keyframes floatBlob {
        0% { transform: translateY(0) scale(1); }
        100% { transform: translateY(-30px) scale(1.1); }
    }
    .neon-text {
        text-shadow: 0 0 10px rgba(228, 48, 109, 0.3), 0 0 20px rgba(27, 34, 153, 0.2);
    }
    .hover-lift {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .hover-lift:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(27, 34, 153, 0.12);
    }
</style>

<div class="relative overflow-hidden min-h-[80vh] flex items-center justify-center bg-gradient-to-br from-slate-50 via-[#f8fafc] to-[#eef2ff]">
    
    <!-- Background Glow Blobs -->
    <div class="absolute top-1/4 left-1/4 w-96 h-96 rounded-full bg-[#1b2299] glow-blob pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full bg-[#e4306d] glow-blob pointer-events-none" style="animation-delay: -5s;"></div>

    <div class="relative max-w-2xl mx-auto px-6 py-20 text-center z-10">
        
        <!-- Big Premium 404 Art -->
        <div class="relative inline-block mb-8">
            <h1 class="text-[9rem] md:text-[12rem] font-extrabold leading-none tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-[#1b2299] to-[#e4306d] neon-text font-outfit select-none">
                404
            </h1>
            <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 px-4 py-1.5 rounded-full bg-white/90 backdrop-blur border border-slate-100 shadow-md text-xs font-bold uppercase tracking-[0.25em] text-[#e4306d]">
                Lost in Space
            </div>
        </div>

        <!-- Copy -->
        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight font-outfit mb-4">
            Oops! This page has drifted away.
        </h2>
        <p class="text-slate-600 text-lg max-w-lg mx-auto leading-relaxed mb-10">
            The link might be broken, or the page may have been moved. Let's get you back on track.
        </p>

        <!-- Search Helper -->
        <div class="max-w-md mx-auto mb-10">
            <form action="blog.php" method="get" class="relative group">
                <input type="text" name="q" placeholder="Search blog posts..." 
                       class="w-full pl-5 pr-12 py-4 rounded-2xl bg-white border border-slate-200 shadow-sm focus:border-[#1b2299] focus:ring-4 focus:ring-[#1b2299]/15 transition-all duration-300 outline-none text-slate-800 font-medium">
                <button type="submit" aria-label="Search" 
                        class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center hover:bg-[#1b2299] transition duration-200">
                    <i class="fas fa-search text-sm"></i>
                </button>
            </form>
        </div>

        <!-- Quick actions -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="index.php" class="hover-lift inline-flex items-center gap-2.5 px-8 py-3.5 rounded-2xl bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-bold text-sm tracking-wide shadow-lg shadow-indigo-900/10">
                <i class="fas fa-home"></i>
                Go to Homepage
            </a>
            <a href="blog.php" class="hover-lift inline-flex items-center gap-2.5 px-8 py-3.5 rounded-2xl bg-white border border-slate-200 text-slate-800 font-bold text-sm tracking-wide shadow-sm">
                <i class="fas fa-newspaper"></i>
                Read our Blog
            </a>
            <a href="index.php#contact" class="hover-lift inline-flex items-center gap-2.5 px-8 py-3.5 rounded-2xl bg-white border border-slate-200 text-slate-800 font-bold text-sm tracking-wide shadow-sm">
                <i class="fas fa-paper-plane"></i>
                Contact Support
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
