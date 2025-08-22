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

$admin_id = $admin_user['id'];
$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE author_id = ? ORDER BY created_at DESC");
$stmt->execute([$admin_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_posts = count($posts);
$published_posts = array_filter($posts, function($post) { return $post['status'] === 'published'; });
$draft_posts = array_filter($posts, function($post) { return $post['status'] === 'draft'; });
$scheduled_posts = array_filter($posts, function($post) { return $post['status'] === 'scheduled'; });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Blog Posts | JLM Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center py-4">
            <div class="flex items-center space-x-3">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-10 w-auto rounded-lg">
                <span class="text-xl font-bold text-gray-900">Blog Management</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="admin_dashboard.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                <a href="blog_create.php" class="px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
                    <i class="fas fa-plus mr-2"></i>Create New Post
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-newspaper text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Posts</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_posts; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Published</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($published_posts); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-edit text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Drafts</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($draft_posts); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Scheduled</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($scheduled_posts); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-eye text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Public Blog</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            <a href="blog.php" target="_blank" class="text-purple-600 hover:text-purple-800">View</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Blog Posts Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Blog Posts</h3>
                    <a href="blog_create.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-plus mr-2"></i>Create New Post
                    </a>
                </div>
            </div>

            <?php if (!empty($posts)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($posts as $post): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if ($post['image']): ?>
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <img class="h-12 w-12 rounded-lg object-cover" src="<?php echo htmlspecialchars($post['image']); ?>" alt="">
                                    </div>
                                    <?php else: ?>
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($post['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($post['slug']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'published' => 'bg-green-100 text-green-800',
                                    'draft' => 'bg-yellow-100 text-yellow-800',
                                    'scheduled' => 'bg-orange-100 text-orange-800'
                                ];
                                $status_color = $status_colors[$post['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_color; ?>">
                                    <?php echo ucfirst($post['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-3">
                                    <a href="blog_edit.php?id=<?php echo $post['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="blog_post.php?slug=<?php echo urlencode($post['slug']); ?>" target="_blank"
                                       class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="blog_delete.php?id=<?php echo $post['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.');"
                                       class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                        Start creating amazing content for your audience. Create your first blog post now!
                    </p>
                    <a href="blog_create.php" 
                       class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white rounded-lg font-semibold hover:from-[#151d7a] hover:to-[#c2285a] transition">
                        <i class="fas fa-plus mr-2"></i>
                        Create Your First Post
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 