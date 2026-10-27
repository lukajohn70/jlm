<?php
if (($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/blog_helpers.php';

$page_title = 'Blog';
$page_description = 'Read the latest JLM creative media updates, project insights, and useful tips from the team.';
$current_page = 'blog';

$q = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

try {
    $params = [];
    $where = "blog_posts.status = 'published'";

    if ($q !== '') {
        $where .= " AND (blog_posts.title LIKE ? OR blog_posts.excerpt LIKE ? OR blog_posts.content LIKE ?)";
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // Optional category support if column exists; fallback if not.
    if ($category !== '') {
        $where .= " AND (blog_posts.category = ?)";
        $params[] = $category;
    }

    $countSql = "
        SELECT COUNT(*)
        FROM blog_posts
        WHERE {$where}
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $sql = "
        SELECT blog_posts.*, COALESCE(users.name, 'JLM Team') AS author
        FROM blog_posts
        LEFT JOIN users ON blog_posts.author_id = users.id
        WHERE {$where}
        ORDER BY COALESCE(blog_posts.published_at, blog_posts.created_at) DESC
        LIMIT {$perPage} OFFSET {$offset}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Category list (best-effort; ignore if column missing)
    $categories = [];
    try {
        $catStmt = $pdo->query("SELECT DISTINCT category FROM blog_posts WHERE status='published' AND category IS NOT NULL AND category <> '' ORDER BY category ASC");
        $categories = array_values(array_filter(array_map(fn($r) => (string) $r['category'], $catStmt->fetchAll(PDO::FETCH_ASSOC))));
    } catch (Throwable $e) {
        $categories = [];
    }
} catch (Exception $e) {
    error_log('Blog query error: ' . $e->getMessage());
    $posts = [];
    $total = 0;
    $totalPages = 1;
    $categories = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
  .blog-card {
    transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease;
  }
  .blog-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 24px 60px rgba(15, 23, 42, .12);
  }
</style>

<section class="relative overflow-hidden bg-gradient-to-br from-white via-[#f8fafc] to-[#eef2ff]">
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(27,34,153,0.12),_transparent_35%),radial-gradient(circle_at_top_right,_rgba(228,48,109,0.12),_transparent_32%)]"></div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
    <div class="max-w-3xl">
      <div class="inline-flex items-center gap-2 rounded-full bg-white/85 px-4 py-2 text-sm font-semibold text-[#1b2299] shadow-sm">
        <i class="fas fa-feather-pointed text-[#e4306d]"></i>
        JLM Blog
      </div>
      <h1 class="mt-6 text-4xl md:text-6xl font-black tracking-tight leading-[1.05] text-gray-900">
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#111827] via-[#1b2299] to-[#e4306d]">
          Ideas, updates, and creative notes from JLM.
        </span>
      </h1>
      <p class="mt-5 text-lg md:text-xl text-gray-600 leading-relaxed">
        A polished view of the latest JLM stories, project highlights, and practical insights, all presented with the same look and feel as the rest of the site.
      </p>

      <form method="get" class="mt-8 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
        <div class="flex-1 relative">
          <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search articles, insights, and updates…" class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 bg-white/90 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1b2299]/20 focus:border-[#1b2299]">
        </div>
        <?php if (!empty($categories)): ?>
          <select name="category" class="sm:w-56 px-4 py-3 rounded-xl border border-gray-200 bg-white/90 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1b2299]/20 focus:border-[#1b2299]">
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat); ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
        <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-semibold shadow-lg hover:scale-[1.01] transition">
          Search
        </button>
      </form>

      <?php if ($q !== '' || $category !== ''): ?>
        <div class="mt-4 text-sm text-gray-600">
          Showing <strong class="text-gray-900"><?php echo (int) $total; ?></strong> result<?php echo $total === 1 ? '' : 's'; ?>
          <?php if ($q !== ''): ?> for “<strong class="text-gray-900"><?php echo htmlspecialchars($q); ?></strong>”<?php endif; ?>
          <?php if ($category !== ''): ?> in <strong class="text-gray-900"><?php echo htmlspecialchars($category); ?></strong><?php endif; ?>.
          <a class="ml-2 text-[#1b2299] font-semibold hover:text-[#e4306d] transition" href="blog.php">Clear</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
  <?php if (!empty($posts)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php foreach ($posts as $post): ?>
        <article class="blog-card group overflow-hidden rounded-[1.75rem] bg-white border border-gray-100 shadow-lg">
          <a href="blog_post.php?slug=<?php echo urlencode($post['slug']); ?>" class="block">
            <?php if (!empty($post['image'])): ?>
              <div class="relative aspect-[4/3] sm:aspect-[16/10] overflow-hidden">
                <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-t from-black/45 via-black/10 to-transparent"></div>
              </div>
            <?php else: ?>
              <div class="aspect-[4/3] sm:aspect-[16/10] bg-gradient-to-br from-[#1b2299] to-[#e4306d] flex items-center justify-center text-white">
                <div class="text-center px-6">
                  <i class="fas fa-newspaper text-4xl mb-3"></i>
                  <p class="text-sm uppercase tracking-[0.24em] text-white/80">JLM Insights</p>
                </div>
              </div>
            <?php endif; ?>
          </a>

          <div class="p-6">
            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 mb-4">
              <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1">
                <i class="fas fa-user text-[#1b2299]"></i>
                <?php echo htmlspecialchars($post['author']); ?>
              </span>
              <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1">
                <i class="fas fa-calendar text-[#e4306d]"></i>
                <?php $publishedTs = strtotime($post['published_at'] ?: $post['created_at']); ?>
                <time class="js-local-datetime" data-ts="<?php echo (int)$publishedTs; ?>" data-format="date">
                  <?php echo date('M j, Y', $publishedTs); ?>
                </time>
              </span>
              <?php if (!empty($post['category'])): ?>
                <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1">
                  <i class="fas fa-tag text-[#1b2299]"></i>
                  <?php echo htmlspecialchars($post['category']); ?>
                </span>
              <?php endif; ?>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-3 leading-tight">
              <a href="blog_post.php?slug=<?php echo urlencode($post['slug']); ?>" class="hover:text-[#1b2299] transition-colors">
                <?php echo htmlspecialchars($post['title']); ?>
              </a>
            </h2>

            <p class="text-gray-600 leading-7 mb-5">
              <?php echo htmlspecialchars(jlm_blog_excerpt($post['content'] ?? '', $post['excerpt'] ?? '', 155)); ?>
            </p>

            <div class="flex items-center justify-between gap-4 pt-4 border-t border-gray-100">
              <a href="blog_post.php?slug=<?php echo urlencode($post['slug']); ?>" class="inline-flex items-center text-[#1b2299] font-semibold hover:text-[#e4306d] transition-colors">
                Read More
                <i class="fas fa-arrow-right ml-2"></i>
              </a>
              <div class="flex items-center gap-2">
                <a href="https://x.com/intent/tweet?text=<?php echo rawurlencode($post['title']); ?>&url=<?php echo rawurlencode(jlm_blog_post_url($post['slug'])); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-black text-white text-xs hover:scale-105 transition" title="Share on X" aria-label="Share on X">
                  <i class="fab fa-x-twitter"></i>
                </a>
                <span class="text-xs uppercase tracking-[0.2em] text-gray-400">JLM Insights</span>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if (($totalPages ?? 1) > 1): ?>
      <div class="mt-12 flex items-center justify-between gap-4">
        <?php
          $baseParams = [];
          if ($q !== '') $baseParams['q'] = $q;
          if ($category !== '') $baseParams['category'] = $category;
          $prevParams = http_build_query(array_merge($baseParams, ['page' => max(1, $page - 1)]));
          $nextParams = http_build_query(array_merge($baseParams, ['page' => min($totalPages, $page + 1)]));
        ?>
        <a class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-200 bg-white font-semibold text-gray-800 <?php echo $page <= 1 ? 'opacity-50 pointer-events-none' : 'hover:border-[#1b2299] hover:text-[#1b2299]'; ?> transition"
           href="blog.php?<?php echo htmlspecialchars($prevParams); ?>">
          <i class="fas fa-arrow-left"></i> Previous
        </a>
        <div class="text-sm text-gray-600">
          Page <strong class="text-gray-900"><?php echo (int) $page; ?></strong> of <strong class="text-gray-900"><?php echo (int) $totalPages; ?></strong>
        </div>
        <a class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-200 bg-white font-semibold text-gray-800 <?php echo $page >= $totalPages ? 'opacity-50 pointer-events-none' : 'hover:border-[#1b2299] hover:text-[#1b2299]'; ?> transition"
           href="blog.php?<?php echo htmlspecialchars($nextParams); ?>">
          Next <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="rounded-[2rem] bg-white border border-gray-100 shadow-lg p-10 md:p-14 text-center max-w-3xl mx-auto">
      <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-newspaper text-3xl text-gray-400"></i>
      </div>
      <h2 class="text-2xl font-bold text-gray-900">No blog posts yet</h2>
      <p class="mt-3 text-gray-600 leading-7">We’re preparing new JLM stories and updates. Please check back soon.</p>
      <div class="mt-8">
        <a href="index.php#home" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-semibold shadow-lg hover:scale-105 transition">
          <i class="fas fa-home"></i>
          Back to Home
        </a>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
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
