<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin_auth();
require_once __DIR__ . '/config/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project = null;
$error = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM client_projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header('Location: admin_client_project_manage.php');
        exit;
    }
}

// Default values
$title = $project['title'] ?? '';
$client_name = $project['client_name'] ?? '';
$version = $project['version'] ?? '1.0.0';
$description = $project['description'] ?? '';
$instructions = $project['instructions'] ?? '';
$demo_url = $project['demo_url'] ?? '';
$passcode = $project['passcode'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $client_name = trim($_POST['client_name'] ?? '');
    $version = trim($_POST['version'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $demo_url = trim($_POST['demo_url'] ?? '');
    $passcode = trim($_POST['passcode'] ?? '');
    
    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        
        // Handle pre-uploaded file
        $file_path = $project['file_path'] ?? null;
        $uploaded_file_path = trim($_POST['uploaded_file_path'] ?? '');
        if (!empty($uploaded_file_path) && strpos($uploaded_file_path, 'uploads/projects/') === 0) {
            if ($file_path && file_exists(__DIR__ . '/' . $file_path)) {
                @unlink(__DIR__ . '/' . $file_path);
            }
            $file_path = $uploaded_file_path;
        }
        
        // Handle expiration
        $expires_value = (int)($_POST['expires_value'] ?? 0);
        $expires_unit = $_POST['expires_unit'] ?? '';
        $expires_at = $project['expires_at'] ?? null;
        if ($expires_value > 0 && in_array($expires_unit, ['days', 'weeks', 'months', 'years'])) {
            $expires_at = date('Y-m-d H:i:s', strtotime("+$expires_value $expires_unit"));
        } elseif (isset($_POST['expires_value']) && $_POST['expires_value'] === '0') {
            $expires_at = null;
        }

        if (empty($error)) {
            try {
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE client_projects SET title = ?, slug = ?, client_name = ?, description = ?, instructions = ?, version = ?, demo_url = ?, file_path = ?, expires_at = ?, passcode = ? WHERE id = ?");
                    $stmt->execute([$title, $slug, $client_name, $description, $instructions, $version, $demo_url, $file_path, $expires_at, $passcode, $id]);
                    header('Location: admin_dashboard.php?tab=deliveries&msg=updated');
                } else {
                    $slug = $slug . '-' . uniqid();
                    $access_token = bin2hex(random_bytes(32));
                    $stmt = $pdo->prepare("INSERT INTO client_projects (title, slug, access_token, passcode, client_name, description, instructions, version, demo_url, file_path, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $slug, $access_token, $passcode, $client_name, $description, $instructions, $version, $demo_url, $file_path, $expires_at]);
                    header('Location: admin_dashboard.php?tab=deliveries&msg=created');
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
    <title><?php echo $id ? 'Edit' : 'Create'; ?> Client Delivery</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
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
            <a href="admin_dashboard.php?tab=deliveries" class="text-gray-500 hover:text-gray-700 mb-2 inline-block">&larr; Back to Deliveries</a>
            <h1 class="text-3xl font-bold text-gray-900"><?php echo $id ? 'Edit Delivery' : 'New Delivery'; ?></h1>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow rounded-lg">
            <form id="deliveryForm" method="POST" class="p-6">
                <input type="hidden" name="uploaded_file_path" id="uploaded_file_path" value="">
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label class="block text-sm font-medium text-gray-700">Project Title *</label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($title); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Version</label>
                        <input type="text" name="version" value="<?php echo htmlspecialchars($version); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Client Name</label>
                        <input type="text" name="client_name" value="<?php echo htmlspecialchars($client_name); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Demo URL</label>
                        <input type="url" name="demo_url" value="<?php echo htmlspecialchars($demo_url); ?>" placeholder="https://" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Instructions (Markdown)</label>
                        <textarea name="instructions" rows="6" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 font-mono text-sm"><?php echo htmlspecialchars($instructions); ?></textarea>
                    </div>
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Expiration (0 = Never)</label>
                        <div class="mt-1 flex space-x-2">
                            <input type="number" name="expires_value" min="0" value="0" class="w-24 border border-gray-300 rounded-md shadow-sm p-2">
                            <select name="expires_unit" class="border border-gray-300 rounded-md shadow-sm p-2">
                                <option value="days">Days</option>
                                <option value="weeks">Weeks</option>
                                <option value="months">Months</option>
                                <option value="years">Years</option>
                            </select>
                        </div>
                    </div>
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Optional Passcode</label>
                        <input type="text" name="passcode" value="<?php echo htmlspecialchars($passcode); ?>" placeholder="Leave blank for no password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <p class="mt-1 text-xs text-gray-500 italic">Adds an extra layer of security before viewing files.</p>
                    </div>

                    <div class="sm:col-span-6 border-t pt-6">
                        
                        <?php if (isset($project['file_path']) && $project['file_path']): ?>
                            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm flex items-center">
                                <i class="fas fa-file-archive text-blue-500 mr-2"></i>
                                <span>Current file: <strong><?php echo basename($project['file_path']); ?></strong></span>
                            </div>
                        <?php endif; ?>

                        <div class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <button type="button" onclick="document.getElementById('fileInput').click()" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-file-upload mr-2"></i> Choose Files
                                </button>
                                <button type="button" onclick="document.getElementById('folderInput').click()" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-folder-open mr-2"></i> Choose Folder
                                </button>
                                <button type="button" id="startUploadBtn" onclick="robustUpload()" disabled class="bg-blue-600 py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 disabled:bg-gray-400">
                                    <i class="fas fa-cloud-upload-alt mr-2"></i> Start Upload
                                </button>
                                <input type="file" id="fileInput" multiple class="hidden" onchange="updateSelectedFiles(this)">
                                <input type="file" id="folderInput" webkitdirectory directory multiple class="hidden" onchange="updateSelectedFiles(this)">
                            </div>

                            <div id="fileInfo" class="hidden text-sm text-gray-600 bg-gray-100 p-3 rounded-md"></div>

                            <div id="progressWrapper" class="hidden space-y-2">
                                <div class="flex justify-between text-sm font-medium text-gray-700">
                                    <span id="progressLabel">Initializing...</span>
                                    <span id="progressPercent">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                                <div id="progressStatus" class="text-xs text-gray-500"></div>
                            </div>

                            <div id="uploadSuccess" class="hidden p-3 bg-green-100 border border-green-200 text-green-700 rounded-md text-sm">
                                <i class="fas fa-check-circle mr-2"></i> Upload complete! You can now save the project.
                            </div>
                        </div>
                    </div>

                    <div class="sm:col-span-6 border-t pt-6 flex justify-end space-x-3">
                        <?php if (!$noLayout): ?>
                        <a href="admin_dashboard.php?tab=deliveries" class="bg-white py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                        <?php else: ?>
                        <button type="button" onclick="parent.closeProjectModal()" class="bg-white py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <?php endif; ?>
                        <button type="submit" id="saveProjectBtn" class="bg-green-600 py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-green-700">
                            <?php echo $id ? 'Save Changes' : 'Create Delivery'; ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedFiles = [];
const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks for better performance

function updateSelectedFiles(input) {
    selectedFiles = Array.from(input.files);
    const fileInfo = document.getElementById('fileInfo');
    const startUploadBtn = document.getElementById('startUploadBtn');
    
    if (selectedFiles.length > 0) {
        let totalSize = selectedFiles.reduce((acc, f) => acc + f.size, 0);
        fileInfo.innerHTML = `<strong>Selected:</strong> ${selectedFiles.length} file(s) - ${(totalSize / (1024 * 1024)).toFixed(2)} MB`;
        fileInfo.classList.remove('hidden');
        startUploadBtn.disabled = false;
        if (input.id === 'fileInput') document.getElementById('folderInput').value = '';
        else document.getElementById('fileInput').value = '';
    }
}

async function robustUpload() {
    if (selectedFiles.length === 0) return;

    const wrapper = document.getElementById('progressWrapper');
    const bar = document.getElementById('progressBar');
    const pct = document.getElementById('progressPercent');
    const label = document.getElementById('progressLabel');
    const status = document.getElementById('progressStatus');
    const startBtn = document.getElementById('startUploadBtn');
    const saveBtn = document.getElementById('saveProjectBtn');
    const successBox = document.getElementById('uploadSuccess');
    const pathInput = document.getElementById('uploaded_file_path');

    wrapper.classList.remove('hidden');
    successBox.classList.add('hidden');
    startBtn.disabled = true;
    saveBtn.disabled = true;

    const uploadId = Date.now().toString() + Math.random().toString(36).substr(2, 5);
    
    try {
        // 1. Client-side Zipping
        label.innerText = 'Preparing files (Zipping)...';
        const zip = new JSZip();
        for (const file of selectedFiles) {
            const relPath = file.webkitRelativePath || file.name;
            zip.file(relPath, file);
        }
        
        const zipBlob = await zip.generateAsync({
            type: 'blob',
            compression: 'DEFLATE',
            compressionOptions: { level: 6 }
        }, (metadata) => {
            pct.innerText = Math.round(metadata.percent) + '%';
            bar.style.width = metadata.percent + '%';
        });

        const totalBytes = zipBlob.size;
        let uploadedBytes = 0;
        const totalChunks = Math.ceil(totalBytes / CHUNK_SIZE);
        const fileName = 'project_delivery.zip';

        // 2. Upload the ZIP in chunks
        for (let c = 0; c < totalChunks; c++) {
            const start = c * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, totalBytes);
            const chunk = zipBlob.slice(start, end);
            
            label.innerText = `Uploading Package: Part ${c + 1} of ${totalChunks}`;

            const fd = new FormData();
            fd.append('upload_id', uploadId);
            fd.append('chunk_index', c);
            fd.append('total_chunks', totalChunks);
            fd.append('relative_name', fileName);
            fd.append('file', chunk);

            const resp = await fetch('admin_upload_handler.php', { method: 'POST', body: fd });
            if (!resp.ok) throw new Error('Upload failed during transmission');
            
            uploadedBytes += (end - start);
            const progress = Math.round((uploadedBytes / totalBytes) * 100);
            bar.style.width = progress + '%';
            pct.innerText = progress + '%';
            status.innerText = `${(uploadedBytes / (1024*1024)).toFixed(2)} MB / ${(totalBytes / (1024*1024)).toFixed(2)} MB`;
        }

        // 3. Finalize
        label.innerText = 'Finalizing upload...';
        const finalFd = new FormData();
        finalFd.append('finalize_upload', '1');
        finalFd.append('upload_id', uploadId);
        finalFd.append('file_name', fileName);
        
        const finalResp = await fetch('admin_upload_handler.php', { method: 'POST', body: finalFd });
        const result = await finalResp.json();

        if (result.success) {
            pathInput.value = result.file_path;
            successBox.classList.remove('hidden');
            label.innerText = 'Upload Complete!';
            bar.style.backgroundColor = '#10B981';
        } else {
            throw new Error(result.error || 'Finalization failed');
        }
    } catch (err) {
        alert('Upload Error: ' + err.message);
        startBtn.disabled = false;
    } finally {
        saveBtn.disabled = false;
    }
}
</script>
</body>
</html>
