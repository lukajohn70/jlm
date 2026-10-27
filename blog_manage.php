<?php
// Helper function to safely get count from table
function safeCount($pdo, $table, $where = '') {
    try {
        $sql = "SELECT COUNT(*) FROM $table";
        if ($where) {
            $sql .= " WHERE $where";
        }
        return $pdo->query($sql)->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// Helper function to safely get data from table
function safeQuery($pdo, $sql) {
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Helper function to safely execute prepared statement
function safeExecute($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/blog_helpers.php';
require_once __DIR__ . '/config/email_utils.php';
require_once __DIR__ . '/includes/blog_schema_migrate.php';
jlm_ensure_blog_posts_schema($GLOBALS['pdo'] ?? null);
$admin_user = require_admin_auth();

$admin_id = $admin_user['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    $action = (string) ($_POST['comment_action'] ?? '');
    if ($commentId > 0 && $action === 'reply') {
        $replyText = trim((string) ($_POST['reply_text'] ?? ''));
        if ($replyText !== '') {
            $lookup = $pdo->prepare('
                SELECT c.id, c.name, c.email, p.id AS post_id, p.slug
                FROM blog_post_comments c
                INNER JOIN blog_posts p ON p.id = c.blog_post_id
                WHERE c.id = ? AND p.author_id = ?
                LIMIT 1
            ');
            $lookup->execute([$commentId, $admin_id]);
            $parent = $lookup->fetch(PDO::FETCH_ASSOC);
            if ($parent) {
                $authorDisplayName = trim((string) ($admin_user['username'] ?? ''));
                if ($authorDisplayName === '') {
                    $authorDisplayName = 'JLM Team';
                }
                $authorEmail = trim((string) ($admin_user['email'] ?? ''));
                $insert = $pdo->prepare('
                    INSERT INTO blog_post_comments (blog_post_id, parent_comment_id, name, email, comment, status, is_author_reply)
                    VALUES (?, ?, ?, ?, ?, "approved", 1)
                ');
                $insert->execute([(int) $parent['post_id'], (int) $parent['id'], $authorDisplayName, $authorEmail !== '' ? $authorEmail : null, $replyText]);

                if (!empty($parent['email']) && filter_var($parent['email'], FILTER_VALIDATE_EMAIL)) {
                    $safeReply = nl2br(htmlspecialchars($replyText, ENT_QUOTES, 'UTF-8'));
                    $postUrl = jlm_blog_post_url((string) $parent['slug']) . '#comments';
                    $content = '
                        <p style="margin-top:0;">Hi ' . htmlspecialchars((string) $parent['name'], ENT_QUOTES, 'UTF-8') . ',</p>
                        <p>The author replied to your comment.</p>
                        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin:14px 0;">' . $safeReply . '</div>
                        <p><a href="' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') . '" style="color:#1b2299;">View conversation</a></p>
                    ';
                    sendEmail((string) $parent['email'], 'Author replied to your comment', getCompleteEmailTemplate('New Reply', $content));
                }
            }
        }
    } elseif ($commentId > 0 && in_array($action, ['approved', 'spam', 'delete'], true)) {
        if ($action === 'delete') {
            $stmt = $pdo->prepare('
                DELETE c FROM blog_post_comments c
                INNER JOIN blog_posts p ON p.id = c.blog_post_id
                WHERE c.id = ? AND p.author_id = ?
            ');
            $stmt->execute([$commentId, $admin_id]);
        } else {
            $stmt = $pdo->prepare('
                UPDATE blog_post_comments c
                INNER JOIN blog_posts p ON p.id = c.blog_post_id
                SET c.status = ?
                WHERE c.id = ? AND p.author_id = ?
            ');
            $stmt->execute([$action, $commentId, $admin_id]);
        }
    }
    header('Location: blog_manage.php');
    exit;
}

$posts = safeExecute($pdo, "
    SELECT
        blog_posts.*,
        (SELECT COUNT(*) FROM blog_post_comments WHERE blog_post_comments.blog_post_id = blog_posts.id AND blog_post_comments.status = 'approved') AS comment_count,
        (SELECT COUNT(*) FROM blog_post_comments WHERE blog_post_comments.blog_post_id = blog_posts.id AND blog_post_comments.status = 'pending') AS pending_comment_count,
        (SELECT COUNT(*) FROM blog_post_likes WHERE blog_post_likes.blog_post_id = blog_posts.id) AS like_count,
        (SELECT share_url FROM blog_social_posts WHERE blog_social_posts.blog_post_id = blog_posts.id AND platform = 'facebook' LIMIT 1) AS facebook_share_url
    FROM blog_posts
    WHERE author_id = ?
    ORDER BY created_at DESC
", [$admin_id]);

$recentComments = safeExecute($pdo, "
    SELECT c.id, c.blog_post_id, c.parent_comment_id, c.is_author_reply, c.name, c.email, c.comment, c.status, c.created_at, p.title, p.slug
    FROM blog_post_comments c
    INNER JOIN blog_posts p ON p.id = c.blog_post_id
    WHERE p.author_id = ?
    ORDER BY c.created_at DESC
    LIMIT 50
", [$admin_id]);

$commentChildren = [];
$commentIndex = [];
foreach ($recentComments as $commentRow) {
    $commentRow['children'] = [];
    $commentIndex[(int) $commentRow['id']] = $commentRow;
}
foreach ($commentIndex as $commentId => $commentRow) {
    $parentId = (int) ($commentRow['parent_comment_id'] ?? 0);
    if ($parentId > 0 && isset($commentIndex[$parentId])) {
        $commentChildren[$parentId][] = $commentRow;
    }
}
$threadedComments = [];
foreach ($commentIndex as $commentId => $commentRow) {
    $parentId = (int) ($commentRow['parent_comment_id'] ?? 0);
    if ($parentId === 0 || !isset($commentIndex[$parentId])) {
        $commentRow['children'] = $commentChildren[$commentId] ?? [];
        usort($commentRow['children'], static function ($a, $b) {
            return strtotime((string) $a['created_at']) <=> strtotime((string) $b['created_at']);
        });
        $threadedComments[] = $commentRow;
    }
}
usort($threadedComments, static function ($a, $b) {
    return strtotime((string) $b['created_at']) <=> strtotime((string) $a['created_at']);
});

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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Engagement</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SEO</th>
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
                                        <?php if (!empty($post['category'])): ?>
                                        <div class="text-xs text-gray-500 mt-1">Category: <?php echo htmlspecialchars($post['category']); ?></div>
                                        <?php endif; ?>
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
                                <div class="flex flex-col">
                                    <span class="inline-flex items-center w-max px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_color; ?>">
                                        <?php echo ucfirst($post['status']); ?>
                                    </span>
                                    <?php if ($post['status'] === 'scheduled' && !empty($post['published_at'])): ?>
                                        <?php $postScheduledTs = strtotime($post['published_at']); ?>
                                        <span class="text-[11px] text-gray-500 mt-1 flex items-center gap-1">
                                            <i class="far fa-clock"></i>
                                            <time class="js-local-datetime" data-ts="<?php echo (int)$postScheduledTs; ?>" data-format="datetime"><?php echo date('M j, Y H:i', $postScheduledTs); ?></time>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                    <span><i class="fas fa-heart text-[#e4306d] mr-1"></i><?php echo (int) ($post['like_count'] ?? 0); ?></span>
                                    <span><i class="fas fa-comments text-[#1b2299] mr-1"></i><?php echo (int) ($post['comment_count'] ?? 0); ?></span>
                                    <?php if ((int) ($post['pending_comment_count'] ?? 0) > 0): ?>
                                    <span class="text-amber-700"><i class="fas fa-hourglass-half mr-1"></i><?php echo (int) ($post['pending_comment_count'] ?? 0); ?> pending</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex flex-wrap gap-2">
                                    <?php if (!empty($post['is_featured'])): ?><span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 text-xs">Featured</span><?php endif; ?>
                                    <?php if (!empty($post['is_pinned'])): ?><span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs">Pinned</span><?php endif; ?>
                                    <?php if (!empty($post['meta_title']) || !empty($post['meta_description'])): ?><span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs">SEO Ready</span><?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php $postCreatedTs = strtotime($post['created_at']); ?>
                                <time class="js-local-datetime" data-ts="<?php echo (int)$postCreatedTs; ?>" data-format="date"><?php echo date('M j, Y', $postCreatedTs); ?></time>
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
                                    <?php if ($post['status'] === 'published' && !empty($post['facebook_share_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($post['facebook_share_url']); ?>" target="_blank"
                                       class="text-[#1877f2] hover:text-[#0f5dc9]" title="Share on Facebook">
                                        <i class="fab fa-facebook"></i>
                                    </a>
                                    <?php endif; ?>
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

        <div class="bg-white rounded-lg shadow mt-8">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Recent Comments</h3>
                <span class="text-sm text-gray-500"><?php echo count($recentComments); ?> comments</span>
            </div>
            <?php if (!empty($threadedComments)): ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($threadedComments as $comment): ?>
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="flex-1">
                            <div class="text-sm text-gray-500">
                                <strong class="text-gray-900"><?php echo htmlspecialchars($comment['name']); ?></strong>
                                <?php if (!empty($comment['email'])): ?> (<?php echo htmlspecialchars($comment['email']); ?>)<?php endif; ?>
                                <?php $commentCreatedTs = strtotime($comment['created_at']); ?>
                                · <time class="js-local-datetime" data-ts="<?php echo (int)$commentCreatedTs; ?>" data-format="datetime"><?php echo date('M j, Y g:i A', $commentCreatedTs); ?></time>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?php echo ($comment['status'] === 'approved') ? 'bg-green-100 text-green-700' : (($comment['status'] === 'spam') ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'); ?>">
                                    <?php echo htmlspecialchars(ucfirst((string) $comment['status'])); ?>
                                </span>
                                <?php if (!empty($comment['is_author_reply'])): ?>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">Author Reply</span>
                                <?php endif; ?>
                                <?php if (!empty($comment['parent_comment_id'])): ?>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">Reply</span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                On post:
                                <a class="text-[#1b2299] hover:text-[#e4306d]" target="_blank" href="blog_post.php?slug=<?php echo urlencode($comment['slug']); ?>">
                                    <?php echo htmlspecialchars($comment['title']); ?>
                                </a>
                            </div>
                            <p class="mt-3 text-gray-700 leading-7"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($comment['status'] !== 'approved'): ?>
                            <form method="post">
                                <input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>">
                                <input type="hidden" name="comment_action" value="approved">
                                <button type="submit" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 transition">Approve</button>
                            </form>
                            <?php endif; ?>
                            <form method="post">
                                <input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>">
                                <input type="hidden" name="comment_action" value="spam">
                                <button type="submit" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition">Mark Spam</button>
                            </form>
                            <form method="post" onsubmit="return confirm('Delete this comment permanently?');">
                                <input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>">
                                <input type="hidden" name="comment_action" value="delete">
                                <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition">Delete</button>
                            </form>
                        </div>
                    </div>
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm font-semibold text-[#1b2299] hover:text-[#e4306d]">Reply as Author</summary>
                        <form method="post" class="mt-3">
                            <input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>">
                            <input type="hidden" name="comment_action" value="reply">
                            <textarea name="reply_text" rows="3" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Write your reply..."></textarea>
                            <button type="submit" class="mt-2 px-4 py-2 rounded-lg bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-semibold hover:from-[#151d7a] hover:to-[#c2285a] transition">Post Reply</button>
                        </form>
                    </details>

                    <?php if (!empty($comment['children'])): ?>
                    <div class="mt-5 ml-0 md:ml-10 space-y-4 border-l-4 border-indigo-100 pl-4">
                        <?php foreach ($comment['children'] as $reply): ?>
                        <div class="rounded-xl bg-indigo-50/60 border border-indigo-100 p-4">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div class="flex-1">
                                    <div class="text-sm text-gray-500">
                                        <strong class="text-gray-900"><?php echo htmlspecialchars($reply['name']); ?></strong>
                                        <?php if (!empty($reply['email'])): ?> (<?php echo htmlspecialchars($reply['email']); ?>)<?php endif; ?>
                                        <?php $replyCreatedTs = strtotime($reply['created_at']); ?>
                                        · <time class="js-local-datetime" data-ts="<?php echo (int) $replyCreatedTs; ?>" data-format="datetime"><?php echo date('M j, Y g:i A', $replyCreatedTs); ?></time>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?php echo ($reply['status'] === 'approved') ? 'bg-green-100 text-green-700' : (($reply['status'] === 'spam') ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'); ?>">
                                            <?php echo htmlspecialchars(ucfirst((string) $reply['status'])); ?>
                                        </span>
                                        <?php if (!empty($reply['is_author_reply'])): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">Author Reply</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-3 text-gray-700 leading-7"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <?php if ($reply['status'] !== 'approved'): ?>
                                    <form method="post">
                                        <input type="hidden" name="comment_id" value="<?php echo (int) $reply['id']; ?>">
                                        <input type="hidden" name="comment_action" value="approved">
                                        <button type="submit" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 transition">Approve</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="post">
                                        <input type="hidden" name="comment_id" value="<?php echo (int) $reply['id']; ?>">
                                        <input type="hidden" name="comment_action" value="spam">
                                        <button type="submit" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition">Mark Spam</button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Delete this comment permanently?');">
                                        <input type="hidden" name="comment_id" value="<?php echo (int) $reply['id']; ?>">
                                        <input type="hidden" name="comment_action" value="delete">
                                        <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="p-8 text-center text-gray-500">No comments yet.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
  document.querySelectorAll('.js-local-datetime[data-ts]').forEach((el) => {
    const ts = Number(el.getAttribute('data-ts'));
    if (!Number.isFinite(ts) || ts <= 0) return;
    const dt = new Date(ts * 1000);
    const format = el.getAttribute('data-format') || 'date';
    const options = format === 'datetime'
      ? { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }
      : { year: 'numeric', month: 'short', day: 'numeric' };
    el.textContent = new Intl.DateTimeFormat(undefined, options).format(dt);
  });
</script>
</html>
