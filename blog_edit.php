<?php
session_start();
require_once __DIR__ . '/config/config.php';
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Get admin user ID from email
$admin_email = $_SESSION['admin_email'] ?? '';
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND role = "admin" LIMIT 1');
$stmt->execute([$admin_email]);
$admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin_user) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

$stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = ? AND author_id = ?');
$stmt->execute([$id, $admin_user['id']]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: blog_manage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    
    if (!$title || !$slug || !$content || !in_array($status, ['draft','published','scheduled'])) {
        $error = 'Please fill all required fields correctly.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            $error = 'Slug already exists. Please choose a different one.';
        } else {
            $stmt = $pdo->prepare('UPDATE blog_posts SET title = ?, slug = ?, content = ?, image = ?, status = ? WHERE id = ?');
            $stmt->execute([$title, $slug, $content, $image, $status, $id]);
            $success = 'Blog post updated successfully!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Blog Post | JLM Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Custom styling for the blog editor */
        .blog-editor-container {
            margin-bottom: 1rem;
        }
    </style>
    <script src="assets/js/rich-text-editor.js"></script>
    <script>
        // Initialize the rich text editor when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Create the editor instance
            window.blogEditor = new RichTextEditor('editor', {
                height: 400,
                placeholder: 'Start writing your blog post content here...',
                uploadUrl: 'upload_image.php',
                toolbar: [
                    'bold', 'italic', 'underline', 'strikethrough',
                    'separator',
                    'alignLeft', 'alignCenter', 'alignRight', 'justifyFull',
                    'separator',
                    'bulletList', 'numberedList',
                    'separator',
                    'fontFamily', 'fontSize', 'textColor', 'backgroundColor',
                    'separator',
                    'createLink', 'insertImage', 'insertVideo', 'insertAudio',
                    'separator',
                    'quote', 'code', 'sectionBreak',
                    'separator',
                    'undo', 'redo',
                    'separator',
                    'clearFormat'
                ],
                onContentChange: (content) => {
                    // Update hidden textarea for form submission
                    document.querySelector('#content-hidden').value = content;
                }
            });
            
            // Load existing content if any
            const existingContent = document.querySelector('#content').innerHTML;
            if (existingContent && existingContent.trim() !== '') {
                window.blogEditor.setContent(existingContent);
            }
            
            // Handle form submission
            document.querySelector('form').addEventListener('submit', function(e) {
                // Ensure content is updated before submission
                document.querySelector('#content-hidden').value = window.blogEditor.getContent();
            });
        });
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center py-4">
            <div class="flex items-center space-x-3">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-10 w-auto rounded-lg">
                <span class="text-xl font-bold text-gray-900">Edit Blog Post</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="blog_manage.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Blog Management
                </a>
                <a href="admin_dashboard.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Alert Messages -->
        <?php if ($error): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
        </div>
        <?php endif; ?>

        <!-- Edit Post Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Blog Post</h2>
            
            <form method="post" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($post['title']); ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter blog post title">
                    </div>
                    
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                            Slug <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="slug" name="slug" required 
                               value="<?php echo htmlspecialchars($post['slug']); ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="blog-post-url-slug">
                        <p class="text-sm text-gray-500 mt-1">URL-friendly version of the title</p>
                    </div>
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                        Featured Image URL
                    </label>
                    <input type="url" id="image" name="image" 
                           value="<?php echo htmlspecialchars($post['image']); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="https://example.com/image.jpg">
                    <p class="text-sm text-gray-500 mt-1">Optional: Add a featured image for your blog post</p>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select id="status" name="status" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="scheduled" <?php echo $post['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Draft posts are not visible to the public</p>
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        Content <span class="text-red-500">*</span>
                    </label>
                    <div id="content" style="display: none;"><?php echo htmlspecialchars($post['content']); ?></div>
                    <div id="editor" class="blog-editor-container"></div>
                    <textarea name="content" id="content-hidden" style="display: none;"><?php echo htmlspecialchars($post['content']); ?></textarea>
                    <p class="text-sm text-gray-500 mt-1">Use the rich text editor above to format your content with bold, italic, colors, headers, and more!</p>
                </div>

                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="blog_manage.php" 
                       class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-lg hover:from-[#151d7a] hover:to-[#c2285a] transition">
                        <i class="fas fa-save mr-2"></i>Update Post
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function() {
            const title = this.value;
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9 -]/g, '') // Remove special characters
                .replace(/\s+/g, '-') // Replace spaces with hyphens
                .replace(/-+/g, '-') // Replace multiple hyphens with single hyphen
                .trim('-'); // Remove leading/trailing hyphens
            
            document.getElementById('slug').value = slug;
        });
    </script>
</body>
</html> 