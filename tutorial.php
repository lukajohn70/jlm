<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    http_response_code(404);
    die("Tutorial not found or invalid token.");
}

$stmt = $pdo->prepare("SELECT * FROM client_tutorials WHERE access_token = ?");
$stmt->execute([$token]);
$tutorial = $stmt->fetch();

if (!$tutorial) {
    http_response_code(404);
    die("Tutorial not found or invalid token.");
}

// Passcode Logic
$passcode_required = !empty($tutorial['passcode']);
$authenticated     = false;
$passcode_error    = false;

if ($passcode_required) {
    $session_key = 'auth_tutorial_' . $tutorial['id'];
    if (isset($_POST['entered_passcode'])) {
        if ($_POST['entered_passcode'] === $tutorial['passcode']) {
            $_SESSION[$session_key] = true;
        } else {
            $passcode_error = true;
        }
    }
    $authenticated = isset($_SESSION[$session_key]) && $_SESSION[$session_key] === true;
} else {
    $authenticated = true;
}

$isExpired = !empty($tutorial['expires_at']) && strtotime($tutorial['expires_at']) < time();

// Build video stream URLs
$base_url    = asset_url('stream_tutorial.php');
$encoded_tok = urlencode($token);
$landscape_src = $base_url . '?token=' . $encoded_tok . '&type=landscape';
$portrait_src  = $base_url . '?token=' . $encoded_tok . '&type=portrait';

$hasLandscape = !empty($tutorial['landscape_video_path']) && file_exists(__DIR__ . '/' . ltrim($tutorial['landscape_video_path'], '/'));
$hasPortrait  = !empty($tutorial['portrait_video_path'])  && file_exists(__DIR__ . '/' . ltrim($tutorial['portrait_video_path'], '/'));
$hasManual    = !empty($tutorial['user_manual_path'])      && file_exists(__DIR__ . '/' . ltrim($tutorial['user_manual_path'], '/'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tutorial['title']); ?> – JLM Tutorial Portal</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" href="<?php echo asset_url('favicon.ico'); ?>" type="image/x-icon">
    <link rel="icon"          href="<?php echo asset_url('favicon.ico'); ?>" type="image/x-icon">
    <link rel="icon" type="image/png" href="<?php echo asset_url('assets/images/jlm.png'); ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(18px) scale(.98); }
            to   { opacity:1; transform:translateY(0)   scale(1);    }
        }
        .fade-up { opacity:0; animation: fadeUp .55s cubic-bezier(.2,.8,.2,1) forwards; }
        .fade-up-1 { animation-delay:.06s; }
        .fade-up-2 { animation-delay:.12s; }
        .fade-up-3 { animation-delay:.18s; }

        video { outline: none; }

        /* Markdown */
        .md-body { font-size:15px; line-height:1.7; color:#374151; }
        .md-body h1,.md-body h2,.md-body h3 { font-weight:700; color:#111827; margin-top:1.4rem; margin-bottom:.6rem; }
        .md-body h1 { font-size:1.6rem; border-bottom:2px solid #e5e7eb; padding-bottom:.4rem; }
        .md-body h2 { font-size:1.3rem; border-bottom:1px solid #f3f4f6; padding-bottom:.2rem; }
        .md-body h3 { font-size:1.1rem; }
        .md-body p  { margin-bottom:.9rem; }
        .md-body a  { color:#1b2299; font-weight:500; text-decoration:underline; }
        .md-body ul,.md-body ol { padding-left:1.4rem; margin-bottom:.9rem; }
        .md-body ul { list-style-type:disc; }
        .md-body ol { list-style-type:decimal; }
        .md-body code {
            background:#f3f4f6; color:#1b2299; border-radius:4px;
            font-size:85%; padding:.15rem .4rem; font-family:monospace;
        }
        .md-body pre {
            background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;
            padding:1rem; overflow-x:auto; margin-bottom:1rem;
        }
        .md-body pre code { background:transparent; color:#111827; padding:0; }
        .md-body blockquote {
            border-left:4px solid #1b2299; padding-left:1rem;
            color:#6b7280; font-style:italic; margin:1rem 0;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">

<!-- ===== HEADER ===== -->
<header class="bg-white shadow-sm border-b sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
        <div class="flex items-center space-x-3">
            <img src="<?php echo asset_url('assets/images/jlm.png'); ?>" alt="JLM Logo" class="h-10 w-auto rounded-lg">
            <div class="flex items-center gap-2">
                <span class="text-xl font-bold text-gray-900">JLM</span>
                <span class="text-gray-400">/</span>
                <span class="text-base font-medium text-gray-600 truncate max-w-[200px] sm:max-w-xs">
                    <?php echo htmlspecialchars($tutorial['title']); ?>
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full border border-gray-200">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            Secure Portal
        </div>
    </div>
</header>

<!-- ===== MAIN ===== -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

<?php if (!$authenticated): ?>
    <!-- Passcode Gate -->
    <div class="max-w-md mx-auto mt-10 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden fade-up">
        <div class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] p-8 text-center">
            <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-lock text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-white">Protected Tutorial</h2>
            <p class="text-white/80 text-sm mt-2">Enter the passcode provided by JLM to access this tutorial.</p>
        </div>
        <div class="p-8">
            <?php if ($passcode_error): ?>
                <div class="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium border border-red-200 flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> Incorrect passcode. Please try again.
                </div>
            <?php endif; ?>
            <form method="POST">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Passcode</label>
                <input type="password" name="entered_passcode" required autofocus
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#1b2299] focus:border-transparent outline-none transition text-gray-800 placeholder-gray-400 mb-6 bg-gray-50"
                       placeholder="••••••••">
                <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-bold rounded-xl hover:opacity-90 active:scale-[0.98] transition shadow-lg">
                    Unlock Tutorial
                </button>
            </form>
        </div>
        <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 text-center text-xs text-gray-400">
            JLM Client Tutorial Portal
        </div>
    </div>

<?php elseif ($isExpired): ?>
    <!-- Expired Screen -->
    <div class="max-w-md mx-auto mt-10 bg-white rounded-2xl shadow-lg border border-gray-100 p-8 text-center fade-up">
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-red-100">
            <i class="fas fa-hourglass-end text-2xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Link Expired</h3>
        <p class="text-gray-500 text-sm mb-6">This tutorial link has expired. Please contact JLM to request a renewed access link.</p>
        <a href="index.php" class="inline-block px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">
            Return to Home
        </a>
    </div>

<?php else: ?>
    <!-- ===== AUTHENTICATED CONTENT ===== -->
    <div class="flex flex-col lg:flex-row gap-6">

        <!-- ===== LEFT COLUMN ===== -->
        <div class="lg:w-3/4 flex flex-col gap-6">

            <!-- VIDEO PLAYER CARD -->
            <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden fade-up fade-up-1">
                <!-- Card Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-2.5 h-2.5 bg-[#1b2299] rounded-full"></div>
                        <h2 class="font-bold text-gray-800 text-base">Video Walkthrough</h2>
                    </div>

                    <!-- View Toggle (only shown if both exist) -->
                    <?php if ($hasLandscape && $hasPortrait): ?>
                    <div class="flex bg-gray-100 p-1 rounded-xl border border-gray-200 text-xs">
                        <button onclick="switchVideoMode('landscape')" id="btn-mode-landscape"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold transition bg-white text-[#1b2299] shadow-sm">
                            <i class="fas fa-desktop"></i> Desktop
                        </button>
                        <button onclick="switchVideoMode('portrait')" id="btn-mode-portrait"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold transition text-gray-500">
                            <i class="fas fa-mobile-alt"></i> Mobile
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Video Stage -->
                <div class="p-6 bg-gray-50 flex items-center justify-center min-h-[380px]">

                    <?php if (!$hasLandscape && !$hasPortrait): ?>
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-gray-100 text-gray-300 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-video-slash text-xl"></i>
                            </div>
                            <h4 class="text-gray-500 font-semibold">No Video Available</h4>
                            <p class="text-gray-400 text-sm mt-1">Please refer to the written instructions below.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Landscape Player -->
                    <?php if ($hasLandscape): ?>
                    <div id="video-container-landscape" class="w-full">
                        <video id="player-landscape" controls controlsList="nodownload"
                               class="w-full aspect-video rounded-xl border border-gray-200 shadow-sm bg-black"
                               preload="metadata">
                            <source src="<?php echo htmlspecialchars($landscape_src); ?>" type="video/mp4">
                            Your browser does not support HTML5 video.
                        </video>
                    </div>
                    <?php endif; ?>

                    <!-- Portrait Player (Phone Mockup) -->
                    <?php if ($hasPortrait): ?>
                    <div id="video-container-portrait" class="<?php echo $hasLandscape ? 'hidden' : ''; ?> w-full flex justify-center py-4">
                        <div class="relative border-[10px] border-gray-800 bg-gray-800 rounded-[3rem] shadow-2xl overflow-hidden aspect-[9/16] w-full max-w-[280px]">
                            <div class="absolute top-0 inset-x-0 h-5 bg-gray-800 flex justify-center z-20">
                                <div class="w-16 h-3.5 bg-black rounded-b-2xl"></div>
                            </div>
                            <video id="player-portrait" controls controlsList="nodownload"
                                   class="w-full h-full object-cover relative z-10 bg-black"
                                   preload="metadata">
                                <source src="<?php echo htmlspecialchars($portrait_src); ?>" type="video/mp4">
                                Your browser does not support HTML5 video.
                            </video>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- INSTRUCTIONS CARD -->
            <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden fade-up fade-up-2">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <i class="fas fa-book-open text-[#1b2299] text-lg"></i>
                    <span class="font-bold text-gray-800">Written Instructions & Guide</span>
                </div>
                <div class="p-6 sm:p-10">
                    <div id="instructions-content" class="md-body">
                        <!-- Rendered by JS below -->
                    </div>
                </div>
            </div>

            <!-- CTA STRIP -->
            <div class="bg-gradient-to-r from-[#1b2299] to-[#e4306d] rounded-2xl shadow p-8 text-white relative overflow-hidden fade-up fade-up-3">
                <div class="absolute -top-10 -right-10 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-white/70 mb-1">JLM Creative Media</p>
                        <h3 class="text-xl font-bold">Need further support?</h3>
                        <p class="text-white/80 text-sm mt-1">Our team is available to help you every step of the way.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 flex-shrink-0">
                        <a href="index.php" class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white font-semibold rounded-xl text-sm transition">
                            <i class="fas fa-globe text-xs"></i> Visit Website
                        </a>
                        <a href="index.php#contact" class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white text-[#1b2299] font-semibold rounded-xl text-sm transition hover:shadow-lg">
                            <i class="fas fa-headset text-xs"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- ===== RIGHT SIDEBAR ===== -->
        <div class="lg:w-1/4 flex flex-col gap-6">

            <!-- Overview Card -->
            <div class="bg-white rounded-2xl shadow border border-gray-100 p-6 fade-up fade-up-1">
                <h3 class="font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100 text-sm uppercase tracking-wider">Tutorial Overview</h3>

                <?php if ($tutorial['description']): ?>
                <p class="text-gray-500 text-sm leading-relaxed mb-5 whitespace-pre-wrap">
                    <?php echo htmlspecialchars($tutorial['description']); ?>
                </p>
                <?php endif; ?>

                <ul class="space-y-4 text-sm">
                    <?php if ($tutorial['client_name']): ?>
                    <li class="flex items-start gap-3">
                        <div class="w-9 h-9 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0 border border-blue-100">
                            <i class="fas fa-building text-[#1b2299] text-xs"></i>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Client</p>
                            <p class="text-gray-700 font-semibold truncate"><?php echo htmlspecialchars($tutorial['client_name']); ?></p>
                        </div>
                    </li>
                    <?php endif; ?>

                    <li class="flex items-start gap-3">
                        <div class="w-9 h-9 bg-pink-50 rounded-xl flex items-center justify-center flex-shrink-0 border border-pink-100">
                            <i class="fas fa-calendar-day text-[#e4306d] text-xs"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Delivered On</p>
                            <p class="text-gray-700 font-semibold"><?php echo date('F j, Y', strtotime($tutorial['created_at'])); ?></p>
                        </div>
                    </li>

                    <?php if (!empty($tutorial['expires_at'])): ?>
                    <li class="flex items-start gap-3">
                        <div class="w-9 h-9 bg-yellow-50 rounded-xl flex items-center justify-center flex-shrink-0 border border-yellow-100">
                            <i class="fas fa-clock text-yellow-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Expires On</p>
                            <p class="text-gray-700 font-semibold"><?php echo date('F j, Y', strtotime($tutorial['expires_at'])); ?></p>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Attachments Card -->
            <?php if ($hasManual || $hasLandscape || $hasPortrait): ?>
            <div class="bg-white rounded-2xl shadow border border-gray-100 p-6 fade-up fade-up-2">
                <h3 class="font-bold text-gray-800 mb-1 text-sm uppercase tracking-wider">Attachments</h3>
                <p class="text-xs text-gray-400 mb-5">Download files for offline access.</p>

                <div class="space-y-3">

                    <?php if ($hasManual): ?>
                    <a href="download_tutorial.php?token=<?php echo $encoded_tok; ?>&type=manual"
                       class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-xl hover:border-[#1b2299] hover:bg-blue-50 transition group">
                        <div class="flex items-center min-w-0 gap-3">
                            <div class="w-9 h-9 bg-blue-100 text-[#1b2299] rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-file-pdf text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-700 truncate"><?php echo basename($tutorial['user_manual_path']); ?></p>
                                <p class="text-[10px] text-gray-400">User Manual / Guide</p>
                            </div>
                        </div>
                        <i class="fas fa-download text-xs text-gray-400 group-hover:text-[#1b2299] ml-2 flex-shrink-0"></i>
                    </a>
                    <?php endif; ?>

                    <?php if ($hasLandscape): ?>
                    <a href="download_tutorial.php?token=<?php echo $encoded_tok; ?>&type=landscape"
                       class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-xl hover:border-green-500 hover:bg-green-50 transition group">
                        <div class="flex items-center min-w-0 gap-3">
                            <div class="w-9 h-9 bg-green-100 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-desktop text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-700 truncate"><?php echo basename($tutorial['landscape_video_path']); ?></p>
                                <p class="text-[10px] text-gray-400">Desktop Video MP4</p>
                            </div>
                        </div>
                        <i class="fas fa-download text-xs text-gray-400 group-hover:text-green-600 ml-2 flex-shrink-0"></i>
                    </a>
                    <?php endif; ?>

                    <?php if ($hasPortrait): ?>
                    <a href="download_tutorial.php?token=<?php echo $encoded_tok; ?>&type=portrait"
                       class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-xl hover:border-purple-500 hover:bg-purple-50 transition group">
                        <div class="flex items-center min-w-0 gap-3">
                            <div class="w-9 h-9 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-mobile-alt text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-700 truncate"><?php echo basename($tutorial['portrait_video_path']); ?></p>
                                <p class="text-[10px] text-gray-400">Mobile Video MP4</p>
                            </div>
                        </div>
                        <i class="fas fa-download text-xs text-gray-400 group-hover:text-purple-600 ml-2 flex-shrink-0"></i>
                    </a>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
<?php endif; ?>
</main>

<!-- FOOTER -->
<footer class="mt-16 py-6 border-t border-gray-100 bg-white text-center text-xs text-gray-400">
    <div class="flex items-center justify-center gap-2 mb-1">
        <img src="<?php echo asset_url('assets/images/jlm.png'); ?>" alt="JLM" class="h-5 w-auto opacity-60">
        <span>&copy; <?php echo date('Y'); ?> JLM Creative Media. Secure Tutorial Portal.</span>
    </div>
</footer>

<script>
<?php if ($authenticated && !$isExpired): ?>

// --- Video mode toggle ---
function switchVideoMode(mode) {
    const cl  = document.getElementById('video-container-landscape');
    const cp  = document.getElementById('video-container-portrait');
    const bl  = document.getElementById('btn-mode-landscape');
    const bp  = document.getElementById('btn-mode-portrait');
    const pl  = document.getElementById('player-landscape');
    const pp  = document.getElementById('player-portrait');

    if (mode === 'landscape') {
        if (cl) cl.classList.remove('hidden');
        if (cp) cp.classList.add('hidden');
        if (bl) { bl.classList.add('bg-white','text-[#1b2299]','shadow-sm'); bl.classList.remove('text-gray-500'); }
        if (bp) { bp.classList.remove('bg-white','text-[#1b2299]','shadow-sm'); bp.classList.add('text-gray-500'); }
        if (pp) pp.pause();
    } else {
        if (cl) cl.classList.add('hidden');
        if (cp) cp.classList.remove('hidden');
        if (bp) { bp.classList.add('bg-white','text-[#1b2299]','shadow-sm'); bp.classList.remove('text-gray-500'); }
        if (bl) { bl.classList.remove('bg-white','text-[#1b2299]','shadow-sm'); bl.classList.add('text-gray-500'); }
        if (pl) pl.pause();
    }
}

// --- Render instructions markdown ---
document.addEventListener('DOMContentLoaded', function () {
    const raw = <?php echo json_encode($tutorial['instructions']); ?>;
    const el  = document.getElementById('instructions-content');
    if (raw && raw.trim()) {
        el.innerHTML = marked.parse(raw);
    } else {
        el.innerHTML = '<p class="text-gray-400 italic text-sm">No written instructions provided.</p>';
    }

    // Set default video mode
    const hasLandscape = <?php echo json_encode($hasLandscape); ?>;
    if (hasLandscape) {
        switchVideoMode('landscape');
    } else {
        switchVideoMode('portrait');
    }
});

<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/cookie_consent.php'; ?>
</body>
</html>
