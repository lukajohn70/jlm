<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin_auth();
require_once __DIR__ . '/config/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tutorial = null;
$error = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM client_tutorials WHERE id = ?");
    $stmt->execute([$id]);
    $tutorial = $stmt->fetch();
    
    if (!$tutorial) {
        header('Location: admin_dashboard.php?tab=tutorials');
        exit;
    }
}

// Default values
$title = $tutorial['title'] ?? '';
$client_name = $tutorial['client_name'] ?? '';
$description = $tutorial['description'] ?? '';
$instructions = $tutorial['instructions'] ?? '';
$passcode = $tutorial['passcode'] ?? '';
$landscape_video_path = $tutorial['landscape_video_path'] ?? '';
$portrait_video_path = $tutorial['portrait_video_path'] ?? '';
$user_manual_path = $tutorial['user_manual_path'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $client_name = trim($_POST['client_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $passcode = trim($_POST['passcode'] ?? '');
    
    // File paths submitted from client-side uploads
    $landscape_video_path = trim($_POST['landscape_video_path'] ?? '');
    $portrait_video_path = trim($_POST['portrait_video_path'] ?? '');
    $user_manual_path = trim($_POST['user_manual_path'] ?? '');
    
    // Clean up old files if they were removed/replaced
    if ($tutorial) {
        if (!empty($landscape_video_path) && $landscape_video_path !== $tutorial['landscape_video_path'] && !empty($tutorial['landscape_video_path'])) {
            @unlink(__DIR__ . '/' . $tutorial['landscape_video_path']);
        }
        if (!empty($portrait_video_path) && $portrait_video_path !== $tutorial['portrait_video_path'] && !empty($tutorial['portrait_video_path'])) {
            @unlink(__DIR__ . '/' . $tutorial['portrait_video_path']);
        }
        if (!empty($user_manual_path) && $user_manual_path !== $tutorial['user_manual_path'] && !empty($tutorial['user_manual_path'])) {
            @unlink(__DIR__ . '/' . $tutorial['user_manual_path']);
        }
        
        // Handle explicit removal
        if (isset($_POST['remove_landscape']) && $_POST['remove_landscape'] == '1') {
            if (!empty($tutorial['landscape_video_path'])) {
                @unlink(__DIR__ . '/' . $tutorial['landscape_video_path']);
            }
            $landscape_video_path = '';
        }
        if (isset($_POST['remove_portrait']) && $_POST['remove_portrait'] == '1') {
            if (!empty($tutorial['portrait_video_path'])) {
                @unlink(__DIR__ . '/' . $tutorial['portrait_video_path']);
            }
            $portrait_video_path = '';
        }
        if (isset($_POST['remove_manual']) && $_POST['remove_manual'] == '1') {
            if (!empty($tutorial['user_manual_path'])) {
                @unlink(__DIR__ . '/' . $tutorial['user_manual_path']);
            }
            $user_manual_path = '';
        }
    }

    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        
        // Handle expiration
        $expires_value = (int)($_POST['expires_value'] ?? 0);
        $expires_unit = $_POST['expires_unit'] ?? '';
        $expires_at = $tutorial['expires_at'] ?? null;
        if ($expires_value > 0 && in_array($expires_unit, ['days', 'weeks', 'months', 'years'])) {
            $expires_at = date('Y-m-d H:i:s', strtotime("+$expires_value $expires_unit"));
        } elseif (isset($_POST['expires_value']) && $_POST['expires_value'] === '0') {
            $expires_at = null;
        }

        if (empty($error)) {
            try {
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE client_tutorials SET title = ?, slug = ?, client_name = ?, description = ?, instructions = ?, passcode = ?, landscape_video_path = ?, portrait_video_path = ?, user_manual_path = ?, expires_at = ? WHERE id = ?");
                    $stmt->execute([$title, $slug, $client_name, $description, $instructions, $passcode, $landscape_video_path, $portrait_video_path, $user_manual_path, $expires_at, $id]);
                    header('Location: admin_dashboard.php?tab=tutorials&msg=updated');
                } else {
                    $slug = $slug . '-' . uniqid();
                    $access_token = bin2hex(random_bytes(32));
                    $stmt = $pdo->prepare("INSERT INTO client_tutorials (title, slug, access_token, passcode, client_name, description, instructions, landscape_video_path, portrait_video_path, user_manual_path, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $slug, $access_token, $passcode, $client_name, $description, $instructions, $landscape_video_path, $portrait_video_path, $user_manual_path, $expires_at]);
                    header('Location: admin_dashboard.php?tab=tutorials&msg=created');
                }
                exit;
            } catch (PDOException $e) {
                $error = 'Database Error: ' . $e->getMessage();
            }
        }
    }
}

$admin_email = $_SESSION['admin_email'] ?? '';
$noLayout = isset($_GET['layout']) && $_GET['layout'] === 'none';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $id ? 'Edit' : 'Create'; ?> Video Tutorial</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        <?php if ($noLayout): ?>
        body { background-color: white; }
        <?php endif; ?>
    </style>
</head>
<body class="<?php echo $noLayout ? 'bg-white' : 'bg-gray-50 min-h-screen'; ?>">
<div class="w-full">
    <?php if (!$noLayout): ?>
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center space-x-3">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-10 w-auto rounded-lg">
                <span class="text-xl font-bold text-gray-900">Admin Dashboard</span>
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end sm:space-x-4">
                <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($admin_email); ?></span>
                <a href="admin_dashboard.php?logout=1" class="inline-flex items-center justify-center px-4 py-2 border border-pink-500 text-pink-600 rounded-lg font-semibold hover:bg-pink-50 transition">Sign Out</a>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <div class="<?php echo $noLayout ? 'p-2' : 'max-w-7xl mx-auto px-4 py-8'; ?>">
        <?php if (!$noLayout): ?>
        <div class="mb-8">
            <a href="admin_dashboard.php?tab=tutorials" class="text-gray-500 hover:text-gray-700 mb-2 inline-block">&larr; Back to Tutorials</a>
            <h1 class="text-3xl font-bold text-gray-900"><?php echo $id ? 'Edit Tutorial' : 'New Tutorial'; ?></h1>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow rounded-lg">
            <form id="tutorialForm" method="POST" class="p-6">
                <!-- Hidden inputs to hold final file paths -->
                <input type="hidden" name="landscape_video_path" id="landscape_video_path" value="<?php echo htmlspecialchars($landscape_video_path); ?>">
                <input type="hidden" name="portrait_video_path" id="portrait_video_path" value="<?php echo htmlspecialchars($portrait_video_path); ?>">
                <input type="hidden" name="user_manual_path" id="user_manual_path" value="<?php echo htmlspecialchars($user_manual_path); ?>">
                
                <!-- Hidden inputs for file deletion -->
                <input type="hidden" name="remove_landscape" id="remove_landscape" value="0">
                <input type="hidden" name="remove_portrait" id="remove_portrait" value="0">
                <input type="hidden" name="remove_manual" id="remove_manual" value="0">

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label class="block text-sm font-medium text-gray-700">Tutorial Title *</label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($title); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    </div>
                    
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Client Name</label>
                        <input type="text" name="client_name" value="<?php echo htmlspecialchars($client_name); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Short Description</label>
                        <textarea name="description" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Detailed Instructions / User Manual Content (Markdown)</label>
                        <textarea name="instructions" rows="6" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 font-mono text-sm focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="Provide extra written guides or documentation here..."><?php echo htmlspecialchars($instructions); ?></textarea>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Expiration (0 = Never)</label>
                        <div class="mt-1 flex space-x-2">
                            <input type="number" name="expires_value" min="0" value="0" class="w-24 border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            <select name="expires_unit" class="border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                                <option value="days">Days</option>
                                <option value="weeks">Weeks</option>
                                <option value="months">Months</option>
                                <option value="years">Years</option>
                            </select>
                        </div>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Optional Passcode</label>
                        <input type="text" name="passcode" value="<?php echo htmlspecialchars($passcode); ?>" placeholder="Leave blank for no password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        <p class="mt-1 text-xs text-gray-500 italic">Forces client to enter a passcode to view the tutorial.</p>
                    </div>

                    <!-- File Upload Grid Section -->
                    <div class="sm:col-span-6 border-t pt-6">
                        <h3 class="text-md font-bold text-gray-900 mb-4">Tutorial Attachments</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            
                            <!-- Landscape Video Upload -->
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-sm font-bold text-gray-700">Landscape Video (16:9)</label>
                                        <i class="fas fa-desktop text-gray-400"></i>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-4">Usually for Desktop/Browser screen walkthroughs.</p>
                                    
                                    <!-- Current file preview -->
                                    <div id="landscape_status" class="mb-4 text-xs">
                                        <?php if ($landscape_video_path): ?>
                                            <div class="p-2 bg-indigo-50 text-indigo-700 rounded-lg flex items-center justify-between">
                                                <span class="truncate max-w-[150px]"><strong>Current:</strong> <?php echo basename($landscape_video_path); ?></span>
                                                <button type="button" onclick="removeAttachment('landscape')" class="text-red-500 hover:text-red-700 ml-1 font-bold" title="Remove video">&times;</button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic">No video uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <button type="button" onclick="document.getElementById('landscapeInput').click()" class="w-full py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                        <i class="fas fa-upload mr-1.5"></i> Select Video
                                    </button>
                                    <input type="file" id="landscapeInput" accept="video/*" class="hidden" onchange="fileSelected('landscape', this)">
                                    
                                    <!-- Progress indicator -->
                                    <div id="landscape_prog_wrapper" class="hidden mt-3 space-y-1">
                                        <div class="flex justify-between text-[10px] font-bold text-gray-500">
                                            <span id="landscape_prog_label">Ready</span>
                                            <span id="landscape_prog_pct">0%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div id="landscape_prog_bar" class="bg-indigo-600 h-1.5 rounded-full" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Portrait Video Upload -->
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-sm font-bold text-gray-700">Portrait Video (9:16)</label>
                                        <i class="fas fa-mobile-alt text-gray-400"></i>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-4">Usually for Mobile App screen recordings.</p>
                                    
                                    <!-- Current file preview -->
                                    <div id="portrait_status" class="mb-4 text-xs">
                                        <?php if ($portrait_video_path): ?>
                                            <div class="p-2 bg-indigo-50 text-indigo-700 rounded-lg flex items-center justify-between">
                                                <span class="truncate max-w-[150px]"><strong>Current:</strong> <?php echo basename($portrait_video_path); ?></span>
                                                <button type="button" onclick="removeAttachment('portrait')" class="text-red-500 hover:text-red-700 ml-1 font-bold" title="Remove video">&times;</button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic">No video uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <button type="button" onclick="document.getElementById('portraitInput').click()" class="w-full py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                        <i class="fas fa-upload mr-1.5"></i> Select Video
                                    </button>
                                    <input type="file" id="portraitInput" accept="video/*" class="hidden" onchange="fileSelected('portrait', this)">
                                    
                                    <!-- Progress indicator -->
                                    <div id="portrait_prog_wrapper" class="hidden mt-3 space-y-1">
                                        <div class="flex justify-between text-[10px] font-bold text-gray-500">
                                            <span id="portrait_prog_label">Ready</span>
                                            <span id="portrait_prog_pct">0%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div id="portrait_prog_bar" class="bg-indigo-600 h-1.5 rounded-full" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Manual Document Upload -->
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-sm font-bold text-gray-700">User Manual / Doc</label>
                                        <i class="fas fa-file-pdf text-gray-400"></i>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-4">Accepts PDFs, Docs, TXT, or ZIP guides.</p>
                                    
                                    <!-- Current file preview -->
                                    <div id="manual_status" class="mb-4 text-xs">
                                        <?php if ($user_manual_path): ?>
                                            <div class="p-2 bg-indigo-50 text-indigo-700 rounded-lg flex items-center justify-between">
                                                <span class="truncate max-w-[150px]"><strong>Current:</strong> <?php echo basename($user_manual_path); ?></span>
                                                <button type="button" onclick="removeAttachment('manual')" class="text-red-500 hover:text-red-700 ml-1 font-bold" title="Remove manual">&times;</button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic">No manual uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <button type="button" onclick="document.getElementById('manualInput').click()" class="w-full py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                        <i class="fas fa-upload mr-1.5"></i> Select Document
                                    </button>
                                    <input type="file" id="manualInput" accept=".pdf,.doc,.docx,.txt,.zip,.rar" class="hidden" onchange="fileSelected('manual', this)">
                                    
                                    <!-- Progress indicator -->
                                    <div id="manual_prog_wrapper" class="hidden mt-3 space-y-1">
                                        <div class="flex justify-between text-[10px] font-bold text-gray-500">
                                            <span id="manual_prog_label">Ready</span>
                                            <span id="manual_prog_pct">0%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div id="manual_prog_bar" class="bg-indigo-600 h-1.5 rounded-full" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Actions Panel -->
                    <div class="sm:col-span-6 border-t pt-6 flex justify-end space-x-3">
                        <?php if (!$noLayout): ?>
                            <a href="admin_dashboard.php?tab=tutorials" class="bg-white py-2.5 px-6 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                        <?php else: ?>
                            <button type="button" onclick="parent.closeTutorialModal()" class="bg-white py-2.5 px-6 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Cancel</button>
                        <?php endif; ?>
                        
                        <button type="submit" id="saveBtn" class="bg-gradient-to-r from-blue-700 to-indigo-600 py-2.5 px-8 rounded-xl shadow-lg text-sm font-bold text-white hover:opacity-90 transition">
                            <?php echo $id ? 'Save Changes' : 'Create Tutorial'; ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const filesToUpload = {
    landscape: null,
    portrait: null,
    manual: null
};

function fileSelected(type, input) {
    if (input.files.length === 0) return;
    const file = input.files[0];
    filesToUpload[type] = file;
    
    // Update label
    const statusDiv = document.getElementById(`${type}_status`);
    statusDiv.innerHTML = `
        <div class="p-2 bg-yellow-50 text-yellow-800 rounded-lg flex items-center justify-between border border-yellow-100">
            <span class="truncate font-semibold max-w-[170px]"><i class="fas fa-file-import mr-1"></i> Ready: ${file.name}</span>
            <button type="button" onclick="clearUploadSelection('${type}')" class="text-red-500 hover:text-red-700 font-bold ml-1">&times;</button>
        </div>
    `;
    
    // Clear any removal triggers if the user uploaded a new one
    document.getElementById(`remove_${type}`).value = '0';
}

function clearUploadSelection(type) {
    filesToUpload[type] = null;
    document.getElementById(`${type}Input`).value = '';
    
    const statusDiv = document.getElementById(`${type}_status`);
    const initialVal = document.getElementById(`${type}_video_path` || `${type}_path`).value;
    
    if (initialVal) {
        // Restore current value
        const path = document.getElementById(`${type === 'manual' ? 'user_manual_path' : type + '_video_path'}`).value;
        statusDiv.innerHTML = `
            <div class="p-2 bg-indigo-50 text-indigo-700 rounded-lg flex items-center justify-between">
                <span class="truncate max-w-[150px]"><strong>Current:</strong> ${path.split('/').pop()}</span>
                <button type="button" onclick="removeAttachment('${type}')" class="text-red-500 hover:text-red-700 ml-1 font-bold">&times;</button>
            </div>
        `;
    } else {
        statusDiv.innerHTML = `<span class="text-gray-400 italic">No file selected</span>`;
    }
}

function removeAttachment(type) {
    if (confirm(`Are you sure you want to remove the current ${type} file when saving?`)) {
        // Set removal input
        document.getElementById(`remove_${type}`).value = '1';
        
        // Clear value holds
        const hiddenInputId = type === 'manual' ? 'user_manual_path' : `${type}_video_path`;
        document.getElementById(hiddenInputId).value = '';
        
        // Update view
        const statusDiv = document.getElementById(`${type}_status`);
        statusDiv.innerHTML = `<span class="text-red-500 italic font-medium"><i class="fas fa-trash-alt mr-1"></i> Will be removed on Save</span>`;
        filesToUpload[type] = null;
    }
}

// Chunked AJAX Upload Helper
const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB

async function uploadFileChunked(file, type, progressCallback) {
    const uploadId = Date.now().toString() + Math.random().toString(36).substr(2, 5);
    const totalBytes = file.size;
    const totalChunks = Math.ceil(totalBytes / CHUNK_SIZE);
    const fileName = file.name;

    for (let c = 0; c < totalChunks; c++) {
        const start = c * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, totalBytes);
        const chunk = file.slice(start, end);

        const fd = new FormData();
        fd.append('upload_id', uploadId);
        fd.append('chunk_index', c);
        fd.append('total_chunks', totalChunks);
        fd.append('relative_name', fileName);
        fd.append('file', chunk);

        const resp = await fetch('admin_tutorial_upload_handler.php', { method: 'POST', body: fd });
        if (!resp.ok) throw new Error(`Upload of part ${c+1} failed.`);

        const progress = Math.round(((start + chunk.size) / totalBytes) * 100);
        progressCallback(progress, `Part ${c + 1} of ${totalChunks} (${progress}%)`);
    }

    // Finalize
    progressCallback(100, 'Reassembling parts...');
    
    const finalFd = new FormData();
    finalFd.append('finalize_upload', '1');
    finalFd.append('upload_id', uploadId);
    finalFd.append('file_name', fileName);

    const finalResp = await fetch('admin_tutorial_upload_handler.php', { method: 'POST', body: finalFd });
    if (!finalResp.ok) throw new Error('Failed to reassemble file on the server');
    
    const result = await finalResp.json();
    if (result.success) {
        return result.file_path;
    } else {
        throw new Error(result.error || 'File reassembly failed');
    }
}

// Form Submission intercept to upload chosen files
document.getElementById('tutorialForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.querySelector('button[type="button"]') || document.querySelector('a');
    
    // Disable buttons
    saveBtn.disabled = true;
    saveBtn.innerText = 'Processing Uploads...';
    if (cancelBtn) cancelBtn.style.pointerEvents = 'none';

    try {
        // Upload Landscape Video
        if (filesToUpload.landscape) {
            const wrapper = document.getElementById('landscape_prog_wrapper');
            const bar = document.getElementById('landscape_prog_bar');
            const pct = document.getElementById('landscape_prog_pct');
            const label = document.getElementById('landscape_prog_label');
            
            wrapper.classList.remove('hidden');
            const path = await uploadFileChunked(filesToUpload.landscape, 'landscape', (p, text) => {
                bar.style.width = p + '%';
                pct.innerText = p + '%';
                label.innerText = text;
            });
            document.getElementById('landscape_video_path').value = path;
            label.innerText = 'Uploaded!';
        }

        // Upload Portrait Video
        if (filesToUpload.portrait) {
            const wrapper = document.getElementById('portrait_prog_wrapper');
            const bar = document.getElementById('portrait_prog_bar');
            const pct = document.getElementById('portrait_prog_pct');
            const label = document.getElementById('portrait_prog_label');
            
            wrapper.classList.remove('hidden');
            const path = await uploadFileChunked(filesToUpload.portrait, 'portrait', (p, text) => {
                bar.style.width = p + '%';
                pct.innerText = p + '%';
                label.innerText = text;
            });
            document.getElementById('portrait_video_path').value = path;
            label.innerText = 'Uploaded!';
        }

        // Upload User Manual
        if (filesToUpload.manual) {
            const wrapper = document.getElementById('manual_prog_wrapper');
            const bar = document.getElementById('manual_prog_bar');
            const pct = document.getElementById('manual_prog_pct');
            const label = document.getElementById('manual_prog_label');
            
            wrapper.classList.remove('hidden');
            const path = await uploadFileChunked(filesToUpload.manual, 'manual', (p, text) => {
                bar.style.width = p + '%';
                pct.innerText = p + '%';
                label.innerText = text;
            });
            document.getElementById('user_manual_path').value = path;
            label.innerText = 'Uploaded!';
        }

        // Submit form
        this.submit();

    } catch (err) {
        alert('An error occurred during upload: ' + err.message);
        saveBtn.disabled = false;
        saveBtn.innerText = 'Save Changes';
        if (cancelBtn) cancelBtn.style.pointerEvents = 'auto';
    }
});
</script>
</body>
</html>
