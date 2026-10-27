<?php
session_start();
require_once __DIR__ . '/config/config.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    http_response_code(404);
    die("Project not found or invalid token.");
}

$stmt = $pdo->prepare("SELECT * FROM client_projects WHERE access_token = ?");
$stmt->execute([$token]);
$project = $stmt->fetch();

if (!$project) {
    http_response_code(404);
    die("Project not found or invalid token.");
}

// Passcode Logic
$passcode_required = !empty($project['passcode']);
$authenticated = false;
$passcode_error = false;

if ($passcode_required) {
    $session_key = 'auth_project_' . $project['id'];
    if (isset($_POST['entered_passcode'])) {
        if ($_POST['entered_passcode'] === $project['passcode']) {
            $_SESSION[$session_key] = true;
        } else {
            $passcode_error = true;
        }
    }
    $authenticated = isset($_SESSION[$session_key]) && $_SESSION[$session_key] === true;
} else {
    $authenticated = true;
}

$hasFile = !empty($project['file_path']) && file_exists(__DIR__ . '/' . $project['file_path']);
$isExpired = !empty($project['expires_at']) && strtotime($project['expires_at']) < time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - Project Delivery</title>
    <!-- Completely hide from search engines -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="assets/images/jlm.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        /* GitHub-like Markdown Styling */
        .markdown-body {
            font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji";
            font-size: 16px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        .markdown-body h1, .markdown-body h2 {
            border-bottom: 1px solid #eaecef;
            padding-bottom: .3em;
        }
        .markdown-body h1 { font-size: 2em; margin-bottom: 16px; font-weight: 600; mt-6; }
        .markdown-body h2 { font-size: 1.5em; margin-bottom: 16px; font-weight: 600; mt-6; }
        .markdown-body h3 { font-size: 1.25em; margin-bottom: 16px; font-weight: 600; mt-6; }
        .markdown-body p { margin-top: 0; margin-bottom: 16px; }
        .markdown-body a { color: #0366d6; text-decoration: none; }
        .markdown-body a:hover { text-decoration: underline; }
        .markdown-body ul, .markdown-body ol { padding-left: 2em; margin-bottom: 16px; }
        .markdown-body ul { list-style-type: disc; }
        .markdown-body ol { list-style-type: decimal; }
        .markdown-body code {
            background-color: rgba(27,31,35,.05);
            border-radius: 3px;
            font-size: 85%;
            margin: 0;
            padding: .2em .4em;
            font-family: SFMono-Regular,Consolas,"Liberation Mono",Menlo,monospace;
        }
        .markdown-body pre {
            background-color: #f6f8fa;
            border-radius: 3px;
            font-size: 85%;
            line-height: 1.45;
            overflow: auto;
            padding: 16px;
            margin-bottom: 16px;
        }
        .markdown-body pre code {
            background-color: transparent;
            border: 0;
            display: inline;
            line-height: inherit;
            margin: 0;
            overflow: visible;
            padding: 0;
            word-wrap: normal;
        }
        .markdown-body blockquote {
            border-left: .25em solid #dfe2e5;
            color: #6a737d;
            padding: 0 1em;
            margin: 0 0 16px 0;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans text-gray-900">

    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="assets/images/jlm.png" alt="JLM Logo" class="h-9 w-auto rounded-lg mr-3">
                    <span class="text-xl font-bold tracking-tight text-gray-900">JLM <span class="text-[#e4306d]">Portal</span></span>
                    <span class="mx-3 text-gray-300">/</span>
                    <span class="text-lg font-medium text-gray-500 truncate max-w-[200px] sm:max-w-none"><?php echo htmlspecialchars($project['title']); ?></span>
                </div>
                <div class="flex items-center">
                    <div class="hidden sm:flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        Secure Connection
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php if (!$authenticated): ?>
            <!-- Passcode Entry Form -->
            <div class="max-w-md mx-auto bg-white border border-gray-200 rounded-3xl shadow-xl overflow-hidden mt-12">
                <div class="bg-gray-900 p-8 text-center">
                    <div class="w-16 h-16 bg-gradient-to-tr from-[#1b2299] to-[#e4306d] text-white rounded-2xl flex items-center justify-center mx-auto mb-4 rotate-3 shadow-lg">
                        <i class="fas fa-lock text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white">Secure Access</h2>
                    <p class="text-gray-400 mt-2">This project is password protected.</p>
                </div>
                <div class="p-8">
                    <?php if ($passcode_error): ?>
                        <div class="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium border border-red-100 flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i> Incorrect passcode. Please try again.
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Enter Passcode</label>
                        <input type="password" name="entered_passcode" required autofocus
                               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#1b2299] focus:border-transparent outline-none transition mb-6"
                               placeholder="••••••••">
                        <button type="submit" class="w-full py-4 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-bold rounded-xl hover:opacity-90 transition shadow-lg shadow-blue-200">
                            Unlock Project
                        </button>
                    </form>
                </div>
                <div class="p-4 bg-gray-50 border-t border-gray-100 text-center text-xs text-gray-400">
                    Secure Delivery via JLM Portal
                </div>
            </div>
        <?php else: ?>
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Left Column: Instructions (Like README.md) -->
                <div class="lg:w-3/4 flex flex-col gap-6">
                    
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center">
                            <i class="fas fa-book text-gray-400 mr-2 text-lg"></i>
                            <span class="font-bold text-gray-700">Instructions / README.md</span>
                        </div>
                        <div class="p-6 sm:p-10">
                            <div id="instructions-content" class="markdown-body">
                                <!-- Markdown will be rendered here -->
                            </div>
                        </div>
                    </div>

                    <!-- CTA and Appreciation Section -->
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-3xl shadow-xl p-8 sm:p-12 text-white relative overflow-hidden">
                        <!-- Decorative element -->
                        <div class="absolute top-0 right-0 -mt-20 -mr-20 w-64 h-64 bg-[#e4306d] opacity-10 rounded-full blur-3xl"></div>
                        <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-64 h-64 bg-[#1b2299] opacity-10 rounded-full blur-3xl"></div>
                        
                        <div class="relative z-10 text-center sm:text-left">
                            <div class="inline-block px-4 py-1.5 bg-white/10 text-white rounded-full text-xs font-bold uppercase tracking-widest mb-4 border border-white/20">Final Delivery</div>
                            <h3 class="text-3xl font-bold mb-4">Thank you for choosing JLM!</h3>
                            <p class="text-gray-300 mb-8 text-lg leading-relaxed max-w-2xl mx-auto sm:mx-0">It has been a pleasure working on this project. We are committed to your long-term success and hope this solution exceeds your expectations. If you have any questions, feel free to reach out.</p>
                            
                            <div class="flex flex-col sm:flex-row gap-4 justify-center sm:justify-start">
                                <a href="index.php" class="flex items-center justify-center gap-2 px-8 py-4 bg-white text-gray-900 rounded-2xl font-bold hover:bg-gray-100 transition shadow-lg">
                                    <i class="fas fa-home"></i> Visit Website
                                </a>
                                <a href="index.php#services" class="flex items-center justify-center gap-2 px-8 py-4 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-2xl font-bold hover:opacity-90 transition shadow-lg">
                                    <i class="fas fa-concierge-bell"></i> Our Services
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Meta & Actions -->
                <div class="lg:w-1/4">
                    
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mb-6">
                        <h3 class="font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3 text-lg">About Project</h3>
                        
                        <?php if ($project['description']): ?>
                            <div class="text-gray-600 mb-6 text-sm leading-relaxed break-words">
                                <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                            </div>
                        <?php endif; ?>

                        <ul class="space-y-4 text-sm">
                            <?php if ($project['client_name']): ?>
                            <li class="flex items-start">
                                <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <div class="ml-3 overflow-hidden">
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Client</p>
                                    <p class="text-gray-900 font-medium truncate"><?php echo htmlspecialchars($project['client_name']); ?></p>
                                </div>
                            </li>
                            <?php endif; ?>
                            
                            <li class="flex items-start">
                                <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-code-branch text-gray-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Version</p>
                                    <p class="text-gray-900 font-medium">v<?php echo htmlspecialchars($project['version']); ?></p>
                                </div>
                            </li>
                            
                            <li class="flex items-start">
                                <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="far fa-calendar-alt text-gray-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Delivery Date</p>
                                    <p class="text-gray-900 font-medium"><?php echo date('F j, Y', strtotime($project['created_at'])); ?></p>
                                </div>
                            </li>

                            <?php if ($project['demo_url']): ?>
                            <li class="mt-4 pt-4 border-t border-gray-50">
                                <a href="<?php echo htmlspecialchars($project['demo_url']); ?>" target="_blank" class="flex items-center justify-center gap-2 w-full py-2.5 bg-gray-50 text-gray-700 rounded-xl font-bold hover:bg-gray-100 transition border border-gray-200">
                                    <i class="fas fa-external-link-alt text-xs text-[#e4306d]"></i> View Live Demo
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <?php if ($hasFile && !$isExpired): ?>
                    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 text-center">
                        <div class="w-16 h-16 bg-gray-50 text-[#1b2299] rounded-2xl flex items-center justify-center mx-auto mb-4 rotate-3 shadow-inner">
                            <i class="fas fa-file-archive text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Source Code</h3>
                        <p class="text-xs text-gray-500 mb-6">Securely download your ZIP package</p>
                        <a href="download_project.php?token=<?php echo urlencode($token); ?>" class="block w-full bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-bold py-3 px-4 rounded-xl shadow-lg hover:opacity-90 transition transform hover:-translate-y-1">
                            <i class="fas fa-download mr-1"></i> Download ZIP
                        </a>
                    </div>
                    <?php elseif ($hasFile && $isExpired): ?>
                    <div class="bg-red-50 border border-red-200 rounded-2xl shadow-sm p-6 text-center">
                        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4 rotate-3">
                            <i class="fas fa-lock text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-red-900 mb-1">Link Expired</h3>
                        <p class="text-xs text-red-500">Please contact support for a new download link.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        <?php if ($authenticated): ?>
        // Render instructions from PHP safely
        const rawMarkdown = <?php echo json_encode($project['instructions']); ?>;
        if (rawMarkdown) {
            document.getElementById('instructions-content').innerHTML = marked.parse(rawMarkdown);
        } else {
            document.getElementById('instructions-content').innerHTML = '<p class="text-gray-400 italic">No instructions provided.</p>';
        }
        <?php endif; ?>
    </script>
</body>
</html>
