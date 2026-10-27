<?php
session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/blog_helpers.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/blog_schema_migrate.php';
jlm_ensure_blog_posts_schema($GLOBALS['pdo'] ?? null);
$admin_user = require_admin_auth();

$error = '';
$success = '';
$formData = [];
$editorAssetVersion = (string) (@filemtime(__DIR__ . '/assets/js/rich-text-editor.js') ?: time());

// Check if form was successfully submitted (for clearing)
if (isset($_SESSION['blog_create_success']) && $_SESSION['blog_create_success']) {
    $success = 'Blog post created successfully!';
    unset($_SESSION['blog_create_success']);
    // Clear form data
    $formData = [];
} else {
    // Get form data for repopulation (if there were errors)
    $formData = $_POST;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $og_image = trim($_POST['og_image'] ?? '');
    $canonical_url = trim($_POST['canonical_url'] ?? '');
    $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
    $is_pinned = !empty($_POST['is_pinned']) ? 1 : 0;
    $status = $_POST['status'] ?? 'draft';
    $author_id = $admin_user['id'];
    
    if (!$title || !$slug || !$content || !in_array($status, ['draft','published','scheduled'])) {
        $error = 'Please fill all required fields correctly.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = ?');
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $error = 'Slug already exists. Please choose a different one.';
        } else {
            $publishedAt = null;
            if ($status === 'published') {
                $publishedAt = date('Y-m-d H:i:s');
            } elseif ($status === 'scheduled') {
                $scheduledTime = trim($_POST['published_at'] ?? '');
                if ($scheduledTime === '') {
                    $error = 'Please select a date and time for scheduling.';
                } else {
                    $publishedAt = date('Y-m-d H:i:s', strtotime($scheduledTime));
                    if (strtotime($publishedAt) <= time()) {
                        $error = 'Schedule date and time must be in the future.';
                    }
                }
            }

            if (!$error) {
                $excerpt = trim(strip_tags($content));
                $excerpt = $excerpt !== '' ? mb_substr($excerpt, 0, 180) : null;
                $readingTime = max(1, (int) ceil(str_word_count(strip_tags($content)) / 200));
                $stmt = $pdo->prepare('
                    INSERT INTO blog_posts
                    (title, slug, excerpt, content, image, category, tags, meta_title, meta_description, og_image, canonical_url, is_featured, is_pinned, reading_time_minutes, author_id, status, published_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $title,
                    $slug,
                    $excerpt,
                    $content,
                    $image ?: null,
                    $category ?: null,
                    $tags ?: null,
                    $meta_title ?: null,
                    $meta_description ?: null,
                    $og_image ?: null,
                    $canonical_url ?: null,
                    $is_featured,
                    $is_pinned,
                    $readingTime,
                    $author_id,
                    $status,
                    $publishedAt
                ]);
                $postId = (int) $pdo->lastInsertId();
                if ($status === 'published') {
                    sync_blog_social_post($pdo, [
                        'id' => $postId,
                        'title' => $title,
                        'slug' => $slug,
                        'excerpt' => $excerpt,
                        'content' => $content,
                        'category' => $category,
                        'tags' => $tags,
                        'status' => $status,
                    ]);
                    jlm_send_new_post_updates($pdo, [
                        'id' => $postId,
                        'title' => $title,
                        'slug' => $slug,
                        'excerpt' => $excerpt,
                        'content' => $content,
                        'status' => $status,
                    ]);
                }
                // Set success flag for form clearing
                $_SESSION['blog_create_success'] = true;
                $success = 'Blog post created successfully!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Blog Post | JLM Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo asset_url('assets/images/jlm.png'); ?>" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Custom styling for the blog editor */
        .blog-editor-container {
            margin-bottom: 1rem;
        }
        .blog-editor-container .jlm-editor {
            overflow: visible;
        }
        .blog-editor-container .jlm-editor-toolbar {
            position: sticky;
            top: calc(5rem + 0.75rem);
            z-index: 20;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
        }
        .blog-editor-container .jlm-editor-content {
            border-radius: 0 0 0.5rem 0.5rem;
        }
    </style>
    <script src="<?php echo asset_url('assets/js/rich-text-editor.js?v=' . rawurlencode($editorAssetVersion)); ?>"></script>
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
                    'heading',
                    'separator',
                    'brandColors',
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
            const initTa = document.getElementById('blog-initial-html');
            if (initTa && initTa.value.trim() !== '') {
                window.blogEditor.setContent(initTa.value);
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
                <img src="<?php echo asset_url('assets/images/jlm.png'); ?>" alt="JLM Logo" class="h-10 w-auto rounded-lg">
                <span class="text-xl font-bold text-gray-900">Create Blog Post</span>
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

        <!-- Create Post Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Create New Blog Post</h2>
            
            <form method="post" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($formData['title'] ?? ''); ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter blog post title">
                    </div>
                    
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                            Slug <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="slug" name="slug" required 
                               value="<?php echo htmlspecialchars($formData['slug'] ?? ''); ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="blog-post-url-slug">
                        <p class="text-sm text-gray-500 mt-1">URL-friendly version of the title</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($formData['category'] ?? ''); ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g. Strategy, Design, Production">
                    </div>
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                        <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($formData['tags'] ?? ''); ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="comma,separated,tags">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Featured Image
                    </label>
                    <input type="hidden" id="image" name="image" value="<?php echo htmlspecialchars($formData['image'] ?? ''); ?>">
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-4">
                        <div class="flex flex-col md:flex-row md:items-center gap-4">
                            <label for="featured-image-upload" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-semibold cursor-pointer hover:from-[#151d7a] hover:to-[#c2285a] transition">
                                <i class="fas fa-upload mr-2"></i>Upload Image
                            </label>
                            <input type="file" id="featured-image-upload" accept="image/*" class="hidden">
                            <div class="flex-1">
                                <p id="featured-image-status" class="text-sm text-gray-500"></p>
                                <div id="featured-image-hint" class="text-xs text-gray-500 space-y-1">
                                    <p class="font-semibold text-gray-700 flex items-center gap-1.5"><i class="fas fa-info-circle text-[#1b2299]"></i> Featured Image Tips</p>
                                    <ul class="space-y-0.5 list-none ml-0.5">
                                        <li><i class="fas fa-ruler-combined text-[#e4306d] w-3.5 mr-1"></i><strong>Ideal size:</strong> 1200 × 630 px (16:9 landscape)</li>
                                        <li><i class="fas fa-weight-hanging text-[#e4306d] w-3.5 mr-1"></i><strong>Max file size:</strong> 2 MB</li>
                                        <li><i class="fas fa-file-image text-[#e4306d] w-3.5 mr-1"></i><strong>Formats:</strong> JPG, PNG, WebP</li>
                                        <li><i class="fab fa-facebook text-[#e4306d] w-3.5 mr-1"></i>This image also appears when shared on Facebook/social media.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div id="featured-image-preview-wrapper" class="<?php echo empty($formData['image']) ? 'hidden' : ''; ?> mt-4">
                            <img id="featured-image-preview" src="<?php echo htmlspecialchars($formData['image'] ?? ''); ?>" alt="Featured preview" class="h-48 w-full max-w-xl object-cover rounded-xl shadow-sm">
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">SEO & Sharing</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                            <input type="text" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($formData['meta_title'] ?? ''); ?>"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Optional SEO title">
                        </div>
                        <div>
                            <label for="canonical_url" class="block text-sm font-medium text-gray-700 mb-1">Canonical URL</label>
                            <input type="url" id="canonical_url" name="canonical_url" value="<?php echo htmlspecialchars($formData['canonical_url'] ?? ''); ?>"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="https://...">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <textarea id="meta_description" name="meta_description" rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Optional SEO description"><?php echo htmlspecialchars($formData['meta_description'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label for="og_image" class="block text-sm font-medium text-gray-700 mb-1">OG Image URL</label>
                            <input type="url" id="og_image" name="og_image" value="<?php echo htmlspecialchars($formData['og_image'] ?? ''); ?>"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="https://...">
                            <div class="mt-3 flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="is_featured" value="1" <?php echo !empty($formData['is_featured']) ? 'checked' : ''; ?>>
                                    Featured post
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="is_pinned" value="1" <?php echo !empty($formData['is_pinned']) ? 'checked' : ''; ?>>
                                    Pin to top
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select id="status" name="status" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="draft" <?php echo ($formData['status'] ?? 'published') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="scheduled" <?php echo ($formData['status'] ?? 'published') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="published" <?php echo ($formData['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Published posts appear on the public blog immediately and get a Facebook-ready share caption.</p>
                </div>

                <div id="schedule-time-container" class="hidden">
                    <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">
                        Schedule Date & Time <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" id="published_at" name="published_at"
                           value="<?php echo !empty($formData['published_at']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($formData['published_at']))) : ''; ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Select the date and time when this blog post should be automatically published.</p>
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        Content <span class="text-red-500">*</span>
                    </label>
                    <div id="editor" class="blog-editor-container"></div>
                    <textarea name="content" id="content-hidden" class="hidden"><?php echo htmlspecialchars($formData['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <textarea id="blog-initial-html" class="hidden" hidden aria-hidden="true"><?php echo htmlspecialchars($formData['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <p class="text-sm text-gray-500 mt-1">Use the rich text editor above to format your content with bold, italic, colors, headers, and more!</p>
                </div>

                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="blog_manage.php" 
                       class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-lg hover:from-[#151d7a] hover:to-[#c2285a] transition">
                        <i class="fas fa-save mr-2"></i>Create Post
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

        const featuredImageUpload = document.getElementById('featured-image-upload');
        const featuredImageInput = document.getElementById('image');
        const featuredImagePreview = document.getElementById('featured-image-preview');
        const featuredImagePreviewWrapper = document.getElementById('featured-image-preview-wrapper');
        const featuredImageStatus = document.getElementById('featured-image-status');

        featuredImageUpload.addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) {
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            featuredImageStatus.textContent = 'Uploading image...';

            try {
                const response = await fetch('upload_image.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (!response.ok || !result.url) {
                    throw new Error(result.error || 'Upload failed.');
                }

                featuredImageInput.value = result.url;
                featuredImagePreview.src = result.url;
                featuredImagePreviewWrapper.classList.remove('hidden');
                featuredImageStatus.textContent = 'Featured image uploaded successfully.';
            } catch (error) {
                featuredImageStatus.textContent = error.message;
            }
        });

        // Toggle Schedule Date/Time field visibility based on selected status
        const statusSelect = document.getElementById('status');
        const scheduleContainer = document.getElementById('schedule-time-container');
        const publishedAtInput = document.getElementById('published_at');

        function toggleScheduleField() {
            if (statusSelect.value === 'scheduled') {
                scheduleContainer.classList.remove('hidden');
                publishedAtInput.required = true;
            } else {
                scheduleContainer.classList.add('hidden');
                publishedAtInput.required = false;
            }
        }

        statusSelect.addEventListener('change', toggleScheduleField);
        toggleScheduleField(); // Run on initialization
    </script>
</body>
</html> 
