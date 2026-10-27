<?php
session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$admin_user = require_admin_auth();
$error = '';
$success = '';

// Handle Edit Mode
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM portfolio WHERE id = ?');
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch();
    if (!$editItem) {
        header('Location: portfolio_manage.php?error=' . urlencode('Portfolio item not found.'));
        exit;
    }
}

if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_portfolio_id'])) {
        $deleteId = (int) $_POST['delete_portfolio_id'];
        $pdo->prepare('DELETE FROM portfolio WHERE id = ?')->execute([$deleteId]);
        $success = 'Portfolio item removed.';
    } elseif (isset($_POST['save_portfolio'])) {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $type = trim($_POST['type'] ?? 'link');
        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');
        $externalUrl = trim($_POST['external_url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $parentId = $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

        if ($title === '') {
            $error = 'Title is required.';
        } else {
            if ($id) {
                $stmt = $pdo->prepare('
                    UPDATE portfolio SET title = ?, category = ?, type = ?, description = ?, image = ?, external_url = ?, sort_order = ?, is_featured = ?, parent_id = ?
                    WHERE id = ?
                ');
                $stmt->execute([$title, $category, $type, $description ?: null, $image ?: null, $externalUrl ?: null, $sortOrder, $isFeatured, $parentId, $id]);
                $success = 'Portfolio item updated successfully.';
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO portfolio (title, category, type, description, image, external_url, sort_order, is_featured, parent_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$title, $category, $type, $description ?: null, $image ?: null, $externalUrl ?: null, $sortOrder, $isFeatured, $parentId]);
                $success = 'Portfolio item added successfully.';
            }
            // Reset edit mode after save
            header('Location: portfolio_manage.php?success=' . urlencode($success));
            exit;
        }
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Fetch all items for the list and for the parent dropdown
$portfolioItems = $pdo->query('SELECT p.*, parent.title as parent_title FROM portfolio p LEFT JOIN portfolio parent ON p.parent_id = parent.id ORDER BY p.parent_id IS NOT NULL, p.sort_order ASC, p.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$potentialParents = $pdo->query('SELECT id, title FROM portfolio WHERE parent_id IS NULL ORDER BY title ASC')->fetchAll(PDO::FETCH_ASSOC);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $editItem ? 'Edit' : 'Manage'; ?> Portfolio | JLM Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(27, 34, 153, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(27, 34, 153, 0); }
            100% { box-shadow: 0 0 0 0 rgba(27, 34, 153, 0); }
        }
        .edit-highlight {
            animation: pulse-ring 2s infinite;
            border-color: #1b2299 !important;
        }
        /* Custom thin scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center py-4">
            <div class="flex items-center space-x-3">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-10 w-auto rounded-lg">
                <span class="text-xl font-bold text-gray-900">Portfolio Management</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="admin_dashboard.php?tab=portfolio" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">Dashboard</a>
                <a href="index.php#portfolio" target="_blank" class="px-4 py-2 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-lg font-semibold hover:from-[#151d7a] hover:to-[#c2285a] transition">View Site</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid lg:grid-cols-[420px_minmax(0,1fr)] gap-8">
        <section id="portfolio-form" class="bg-white rounded-2xl shadow p-6 h-fit sticky top-24 overflow-y-auto max-h-[calc(100vh-120px)] custom-scrollbar <?php echo $editItem ? 'edit-highlight' : ''; ?>">
            <h2 class="text-2xl font-bold text-gray-900 mb-6"><?php echo $editItem ? 'Edit' : 'Add'; ?> Portfolio Item</h2>
            <?php if ($error): ?>
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form id="portfolio-form-el" method="post" class="space-y-4">
                <input type="hidden" name="save_portfolio" value="1">
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                <?php endif; ?>
                
                <div>
                    <label for="title" class="block text-sm font-bold text-gray-700 mb-0.5">Title</label>
                    <p class="text-[11px] text-gray-500 mb-2 italic">The main heading for this item (e.g. "Creative Graphics Portfolio" or "Sokatwa Website").</p>
                    <input id="title" name="title" type="text" required value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-[#1b2299] focus:ring-2 focus:ring-[#1b2299]/20 outline-none transition-all">
                </div>

                <div>
                    <label for="parent_id" class="block text-sm font-bold text-gray-700 mb-0.5">Parent Category (Group)</label>
                    <p class="text-[11px] text-gray-500 mb-2 italic">Select "None" for main cards on the homepage, or select a category to put this inside a modal.</p>
                    <select id="parent_id" name="parent_id" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-[#1b2299] focus:ring-2 focus:ring-[#1b2299]/20 outline-none transition-all">
                        <option value="">None (This is a Main Category Card)</option>
                        <?php foreach ($potentialParents as $p): ?>
                            <?php if ($editItem && $p['id'] == $editItem['id']) continue; ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($editItem['parent_id'] ?? '') == $p['id'] ? 'selected' : ''; ?>>
                                Sub-item of: <?php echo htmlspecialchars($p['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="category" class="block text-sm font-bold text-gray-700 mb-0.5">Category Label</label>
                    <p class="text-[11px] text-gray-500 mb-2 italic">The small badge text shown above the title (e.g. "Web Design").</p>
                    <input id="category" name="category" type="text" value="<?php echo htmlspecialchars($editItem['category'] ?? ''); ?>" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-[#1b2299] focus:ring-2 focus:ring-[#1b2299]/20 outline-none transition-all" placeholder="e.g. Video Production, Graphics Design">
                </div>

                <div>
                    <label for="type" class="block text-sm font-bold text-gray-700 mb-0.5">Display/Action Type</label>
                    <p class="text-[11px] text-gray-500 mb-2 italic">How this item should behave when clicked.</p>
                    <select id="type" name="type" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-[#1b2299] focus:ring-2 focus:ring-[#1b2299]/20 outline-none transition-all">
                        <option value="gwr" <?php echo ($editItem['type'] ?? '') === 'gwr' ? 'selected' : ''; ?>>Guinness World Record (Case Study & Modal)</option>
                        <option value="link" <?php echo ($editItem['type'] ?? '') === 'link' ? 'selected' : ''; ?>>Standard Link / Website (Direct Redirect)</option>
                        <option value="video" <?php echo ($editItem['type'] ?? '') === 'video' ? 'selected' : ''; ?>>Video Modal (Pop-up Player)</option>
                        <option value="music" <?php echo ($editItem['type'] ?? '') === 'music' ? 'selected' : ''; ?>>Music Showcase Modal (Multiple Embeds)</option>
                        <option value="channel" <?php echo ($editItem['type'] ?? '') === 'channel' ? 'selected' : ''; ?>>YouTube Channel Modal (Subscription Prompt)</option>
                        <option value="web" <?php echo ($editItem['type'] ?? '') === 'web' ? 'selected' : ''; ?>>Web Design Portfolio Modal (Grid of Links)</option>
                        <option value="graphics" <?php echo ($editItem['type'] ?? '') === 'graphics' ? 'selected' : ''; ?>>Graphics Design Modal (Image Gallery)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-0.5">Image URL / Thumbnail</label>
                    <p class="text-[11px] text-gray-500 mb-2 italic">The main visual for this item. Upload a file or paste a direct image link.</p>
                    <div class="flex gap-2">
                        <input type="text" id="image" name="image" value="<?php echo htmlspecialchars($editItem['image'] ?? ''); ?>" class="flex-1 rounded-xl border border-gray-300 px-4 py-3 focus:border-[#1b2299] focus:ring-2 focus:ring-[#1b2299]/20 outline-none transition-all" placeholder="https://...">
                        <button type="button" id="clear-image-button" class="px-4 py-3 rounded-xl border border-red-200 bg-red-50 text-red-600 font-semibold hover:bg-red-100 transition">Clear</button>
                    </div>
                    <div class="mt-2 rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-4 transition-all hover:bg-gray-100">
                        <label for="portfolio-image-upload" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 font-semibold cursor-pointer hover:bg-gray-50 transition shadow-sm">
                            <i class="fas fa-upload mr-2 text-[#1b2299]"></i>Upload File
                        </label>
                        <input type="file" id="portfolio-image-upload" accept="image/*" class="hidden">
                        <p id="portfolio-image-status" class="mt-2 text-[10px] text-gray-400">Recommended size: 800x600px (JPG/PNG)</p>
                    </div>
                    <!-- Image Preview -->
                    <div id="image-preview-container" class="mt-3 hidden">
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Image Preview:</p>
                        <img id="image-preview" src="" class="w-full h-32 object-cover rounded-lg border border-gray-200 shadow-sm bg-white">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-bold text-gray-700 mb-0.5">Short Description</label>
                    <p class="text-[11px] text-gray-500 mb-2 italic">A brief summary of the project (max 150 characters).</p>
                    <textarea id="description" name="description" rows="2" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-[#1b2299] focus:ring-2 focus:ring-[#1b2299]/20 outline-none transition-all" placeholder="Tell us about this project..."><?php echo htmlspecialchars($editItem['description'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label for="external_url" class="block text-sm font-bold text-gray-700 mb-0.5">External Link / Video URL</label>
                    <p class="text-[11px] text-gray-500 mb-2 italic">The destination URL. Use YouTube links for videos.</p>
                    <input id="external_url" name="external_url" type="text" value="<?php echo htmlspecialchars($editItem['external_url'] ?? ''); ?>" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-[#1b2299] focus:ring-2 focus:ring-[#1b2299]/20 outline-none transition-all" placeholder="https://youtube.com/watch?v=...">
                    <!-- Link Preview Area -->
                    <div id="link-preview-container" class="mt-3 hidden p-3 rounded-xl border border-gray-200 bg-gray-50">
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Link Preview:</p>
                        <div id="link-preview-content" class="flex gap-3 items-center">
                            <!-- Preview Content via JS -->
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="sort_order" class="block text-sm font-bold text-gray-700 mb-0.5">Sort Order</label>
                        <p class="text-[11px] text-gray-500 mb-2 italic">Lower numbers appear first.</p>
                        <input id="sort_order" name="sort_order" type="number" value="<?php echo (int)($editItem['sort_order'] ?? 0); ?>" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-[#1b2299] focus:ring-2 focus:ring-[#1b2299]/20 outline-none transition-all">
                    </div>
                    <div class="flex items-end pb-3">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer group">
                            <input type="checkbox" name="is_featured" <?php echo !isset($editItem) || !empty($editItem['is_featured']) ? 'checked' : ''; ?> class="w-5 h-5 rounded border-gray-300 text-[#1b2299] focus:ring-[#1b2299] transition-all">
                            <div class="flex flex-col">
                                <span class="font-bold group-hover:text-[#1b2299] transition-colors">Featured Item</span>
                                <span class="text-[10px] text-gray-400">Prioritize on homepage</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-100 mt-6">
                    <?php if ($editItem): ?>
                        <a href="portfolio_manage.php" class="flex-1 inline-flex items-center justify-center px-5 py-3 rounded-xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="flex-[2] inline-flex items-center justify-center gap-2 px-5 py-4 rounded-xl bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-bold shadow-lg hover:shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
                        <i class="fas <?php echo $editItem ? 'fa-save' : 'fa-plus-circle'; ?> text-lg"></i>
                        <span><?php echo $editItem ? 'Save Changes' : 'Add to Portfolio'; ?></span>
                    </button>
                </div>
            </form>
        </section>

        <section class="bg-white rounded-2xl shadow p-6">
            <div class="flex items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Portfolio Items</h2>
                    <p class="text-sm text-gray-500 mt-1">Homepage categories and their sub-items.</p>
                </div>
                <span class="inline-flex items-center px-4 py-2 rounded-full bg-gray-100 text-gray-700 text-sm font-semibold"><?php echo count($portfolioItems); ?> total</span>
            </div>

            <?php if (empty($portfolioItems)): ?>
                <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-12 text-center">
                    <i class="fas fa-folder-open text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No portfolio items yet. Add one to get started!</p>
                </div>
            <?php else: ?>
                <div class="grid md:grid-cols-1 xl:grid-cols-2 gap-6">
                    <?php foreach ($portfolioItems as $item): ?>
                        <?php $displayImage = get_portfolio_display_image($item); ?>
                        <article class="rounded-2xl border <?php echo $item['parent_id'] ? 'border-gray-100 bg-gray-50/50 ml-8' : 'border-gray-200 bg-white'; ?> overflow-hidden flex flex-col transition-all">
                            <div class="relative h-40">
                                <?php if (!empty($displayImage)): ?>
                                    <img src="<?php echo htmlspecialchars($displayImage); ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                        <i class="fas fa-image text-2xl text-gray-300"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-3 right-3 flex gap-2">
                                    <span class="px-2 py-1 rounded bg-black/50 text-white text-[9px] uppercase font-bold backdrop-blur-sm">
                                        <?php echo htmlspecialchars($item['type']); ?>
                                    </span>
                                </div>
                                <?php if ($item['parent_id']): ?>
                                    <div class="absolute bottom-0 left-0 right-0 bg-[#1b2299] text-white text-[9px] px-3 py-1 font-bold uppercase tracking-wider">
                                        Sub-item of: <?php echo htmlspecialchars($item['parent_title']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4 flex-1 flex flex-col">
                                <div class="flex items-start justify-between gap-4 mb-1">
                                    <h3 class="text-base font-bold text-gray-900 line-clamp-1"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <span class="text-[10px] font-medium text-gray-400">#<?php echo (int) $item['sort_order']; ?></span>
                                </div>
                                <?php if ($item['category']): ?>
                                    <span class="inline-block bg-[#1b2299]/10 text-[#1b2299] px-2 py-0.5 rounded text-[10px] font-bold uppercase mb-2 self-start"><?php echo htmlspecialchars($item['category']); ?></span>
                                <?php endif; ?>
                                <p class="text-xs text-gray-600 line-clamp-2 mb-4"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                                
                                <div class="mt-auto pt-3 border-t border-gray-100 flex items-center justify-between gap-3">
                                    <div class="flex gap-2">
                                        <a href="portfolio_manage.php?edit=<?php echo $item['id']; ?>#portfolio-form" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-[10px] font-bold hover:bg-gray-200 transition">
                                            <i class="fas fa-edit"></i> EDIT
                                        </a>
                                        <form method="post" onsubmit="return confirm('Remove this portfolio item?');" class="inline">
                                            <input type="hidden" name="delete_portfolio_id" value="<?php echo (int) $item['id']; ?>">
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-red-50 text-red-600 text-[10px] font-bold hover:bg-red-100 transition">
                                                <i class="fas fa-trash"></i> DELETE
                                            </button>
                                        </form>
                                    </div>
                                    <?php if (!empty($item['is_featured'])): ?>
                                        <span class="text-[9px] font-bold text-green-600 uppercase flex items-center gap-1">
                                            <i class="fas fa-check-circle"></i> Featured
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        const portfolioImageUpload = document.getElementById('portfolio-image-upload');
        const portfolioImageInput = document.getElementById('image');
        const portfolioImageStatus = document.getElementById('portfolio-image-status');
        const externalUrlInput = document.getElementById('external_url');
        const clearImageButton = document.getElementById('clear-image-button');

        const imagePreviewContainer = document.getElementById('image-preview-container');
        const imagePreview = document.getElementById('image-preview');
        const linkPreviewContainer = document.getElementById('link-preview-container');
        const linkPreviewContent = document.getElementById('link-preview-content');

        function getYoutubePreview(url) {
            const youtubeMatch = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
            return youtubeMatch ? `https://img.youtube.com/vi/${youtubeMatch[1]}/hqdefault.jpg` : '';
        }

        function getLinkPreviewImage(url) {
            if (!url || !url.startsWith('http')) {
                return '';
            }

            return getYoutubePreview(url) || `https://s.wordpress.com/mshots/v1/${encodeURIComponent(url)}?w=1200`;
        }

        function updateImagePreview() {
            const imageUrl = portfolioImageInput.value.trim();
            const fallbackUrl = getLinkPreviewImage(externalUrlInput.value.trim());
            const previewUrl = imageUrl || fallbackUrl;

            if (previewUrl) {
                imagePreview.src = previewUrl;
                imagePreviewContainer.classList.remove('hidden');
            } else {
                imagePreview.removeAttribute('src');
                imagePreviewContainer.classList.add('hidden');
            }
        }

        function updateLinkPreview() {
            const url = externalUrlInput.value.trim();
            if (!url) {
                linkPreviewContainer.classList.add('hidden');
                return;
            }

            linkPreviewContainer.classList.remove('hidden');
            let previewHtml = '';

            // Check if YouTube
            const youtubePreview = getYoutubePreview(url);
            
            if (youtubePreview) {
                previewHtml = `
                    <div class="relative w-24 h-16 flex-shrink-0 bg-gray-200 rounded overflow-hidden">
                        <img src="${youtubePreview}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                            <i class="fas fa-play text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-gray-900 truncate">YouTube Video</p>
                        <p class="text-[10px] text-gray-500 truncate">${url}</p>
                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-red-100 text-red-600 text-[8px] font-bold uppercase">Video</span>
                    </div>
                `;
            } else if (url.startsWith('http')) {
                // Generic website preview using Microlink
                previewHtml = `
                    <div class="w-24 h-16 flex-shrink-0 bg-gray-200 rounded overflow-hidden">
                        <img src="${getLinkPreviewImage(url)}" class="w-full h-full object-cover" onerror="this.src='https://via.placeholder.com/150?text=No+Preview'">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-gray-900 truncate">Website Preview</p>
                        <p class="text-[10px] text-gray-500 truncate">${url}</p>
                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-blue-100 text-blue-600 text-[8px] font-bold uppercase">External Link</span>
                    </div>
                `;
            } else {
                linkPreviewContainer.classList.add('hidden');
            }

            linkPreviewContent.innerHTML = previewHtml;
            updateImagePreview();
        }

        portfolioImageInput.addEventListener('input', updateImagePreview);
        externalUrlInput.addEventListener('input', updateLinkPreview);
        clearImageButton.addEventListener('click', function () {
            portfolioImageInput.value = '';
            portfolioImageStatus.textContent = 'Thumbnail cleared. The link preview will be used instead when available.';
            portfolioImageStatus.className = 'mt-2 text-xs text-amber-600 font-medium';
            updateImagePreview();
        });

        // Initial preview on load
        updateImagePreview();
        updateLinkPreview();

        portfolioImageUpload.addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);
            portfolioImageStatus.textContent = 'Uploading...';
            portfolioImageStatus.className = 'mt-2 text-xs text-blue-600 font-medium';

            try {
                const response = await fetch('upload_image.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (!response.ok || !result.url) {
                    throw new Error(result.error || 'Upload failed.');
                }

                portfolioImageInput.value = result.url;
                updateImagePreview();
                portfolioImageStatus.textContent = 'Success!';
                portfolioImageStatus.className = 'mt-2 text-xs text-green-600 font-medium';
            } catch (error) {
                portfolioImageStatus.textContent = error.message;
                portfolioImageStatus.className = 'mt-2 text-xs text-red-600 font-medium';
            }
        });
    </script>
</body>
</html>
