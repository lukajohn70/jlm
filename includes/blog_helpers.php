<?php

function jlm_base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/jlm/index.php';
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if ($basePath === '' || $basePath === '.') {
        $basePath = '';
    }

    return $scheme . '://' . $host . $basePath;
}

function jlm_blog_post_url($slug) {
    return jlm_base_url() . '/blog_post.php?slug=' . rawurlencode((string) $slug);
}

function jlm_decode_rich_text($content) {
    $decoded = (string) $content;
    // Undo multiple layers of HTML entity encoding (common when content was stored/served escaped).
    $prev = '';
    for ($i = 0; $i < 8 && $decoded !== $prev; $i++) {
        $prev = $decoded;
        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $decoded = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $decoded);
    $decoded = preg_replace('#<style\b[^>]*>(.*?)</style>#is', '', $decoded);
    $decoded = preg_replace('/on[a-z]+\s*=\s*"[^"]*"/i', '', $decoded);
    $decoded = preg_replace("/on[a-z]+\s*=\s*'[^']*'/i", '', $decoded);
    $decoded = preg_replace('/javascript:/i', '', $decoded);

    // NotebookLM / tool exports: inline "math" spans → readable scripture-style refs
    $decoded = preg_replace_callback(
        '/<span\b[^>]*\bdata-math="([^"]*)"[^>]*>[\s\S]*?<\/span>|<span\b[^>]*\bdata-math=\'([^\']*)\'[^>]*>[\s\S]*?<\/span>/iu',
        static function ($m) {
            $raw = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
            $ref = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return '<cite class="jlm-scripture-ref">' . htmlspecialchars($ref, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</cite>';
        },
        $decoded
    );
    // Raw $Book 1:1$ style markers (often pasted from AI tools); avoid short/$5-style matches
    $decoded = preg_replace('/\(\s*\$([^$]{2,120})\$\s*\)/u', '<cite class="jlm-scripture-ref">$1</cite>', $decoded);
    $decoded = preg_replace('/\$([^$\d][^$]{1,118})\$/u', '<cite class="jlm-scripture-ref">$1</cite>', $decoded);

    $allowedTags = '<p><br><div><span><strong><b><em><i><u><s><strike><ul><ol><li><blockquote><a><img><h1><h2><h3><h4><h5><h6><hr><pre><code><video><audio><source><figure><figcaption><cite><sub><sup><mark>';
    return strip_tags($decoded, $allowedTags);
}

function jlm_blog_plain_text($content) {
    $decoded = jlm_decode_rich_text($content);
    $text = html_entity_decode(strip_tags((string) $decoded), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', trim($text));
    return $text;
}

function jlm_blog_excerpt($content, $fallback = '', $length = 180) {
    $source = trim((string) $fallback);
    if ($source !== '') {
        $source = jlm_blog_plain_text($source);
    }
    if ($source === '') {
        $source = jlm_blog_plain_text($content);
    }

    if (mb_strlen($source) <= $length) {
        return $source;
    }

    return rtrim(mb_substr($source, 0, $length - 1)) . '…';
}

function jlm_social_summary_from_post(array $post) {
    $summary = jlm_blog_excerpt($post['content'] ?? '', $post['excerpt'] ?? '', 180);
    return trim($summary !== '' ? $summary : ($post['title'] ?? 'New post from JLM'));
}

function sync_blog_social_post(PDO $pdo, array $post) {
    if (($post['status'] ?? '') !== 'published' || empty($post['id']) || empty($post['slug'])) {
        return;
    }

    $url = jlm_blog_post_url($post['slug']);
    $summary = jlm_social_summary_from_post($post);
    $caption = trim(($post['title'] ?? 'JLM Blog') . "\n\n" . $summary . "\n\nRead more: " . $url);
    $shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url);

    $stmt = $pdo->prepare("
        INSERT INTO blog_social_posts (blog_post_id, platform, status, summary, share_caption, post_url, share_url, generated_at)
        VALUES (?, 'facebook', 'ready', ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            summary = VALUES(summary),
            share_caption = VALUES(share_caption),
            post_url = VALUES(post_url),
            share_url = VALUES(share_url),
            generated_at = NOW()
    ");
    $stmt->execute([(int) $post['id'], $summary, $caption, $url, $shareUrl]);
}

function get_blog_social_post(PDO $pdo, $blogPostId, $platform = 'facebook') {
    $stmt = $pdo->prepare('SELECT * FROM blog_social_posts WHERE blog_post_id = ? AND platform = ? LIMIT 1');
    $stmt->execute([(int) $blogPostId, $platform]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function jlm_send_new_post_updates(PDO $pdo, array $post): int {
    if (($post['status'] ?? '') !== 'published' || empty($post['id']) || empty($post['slug'])) {
        return 0;
    }

    // Send only once per post.
    $guardStmt = $pdo->prepare('
        INSERT IGNORE INTO blog_email_notifications (blog_post_id, notification_type)
        VALUES (?, "new_post")
    ');
    $guardStmt->execute([(int) $post['id']]);
    if ($guardStmt->rowCount() === 0) {
        return 0;
    }

    require_once __DIR__ . '/../config/email_utils.php';

    $title = (string) ($post['title'] ?? 'New post from JLM');
    $summary = jlm_social_summary_from_post($post);
    $url = jlm_blog_post_url((string) $post['slug']);
    $content = '
        <p style="margin-top:0;">A new blog post has just been published by JLM:</p>
        <h3 style="margin:10px 0 6px;color:#111827;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>
        <p style="margin:0 0 14px;color:#374151;">' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . '</p>
        <p><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:linear-gradient(135deg,#1b2299,#e4306d);color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700;">Read New Post</a></p>
    ';

    $stmt = $pdo->query("SELECT email, first_name FROM email_subscribers WHERE status = 'active'");
    $subscribers = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $recipientMap = [];
    foreach ($subscribers as $subscriber) {
        $to = trim((string) ($subscriber['email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        $recipientMap[strtolower($to)] = [
            'email' => $to,
            'first_name' => trim((string) ($subscriber['first_name'] ?? ''))
        ];
    }

    // Add past commenters to publication updates (unless explicitly unsubscribed).
    try {
        $commenterStmt = $pdo->query("
            SELECT DISTINCT email, name
            FROM blog_post_comments
            WHERE email IS NOT NULL AND email <> ''
        ");
        $commenters = $commenterStmt ? $commenterStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($commenters as $commenter) {
            $to = trim((string) ($commenter['email'] ?? ''));
            if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $subStatusStmt = $pdo->prepare("SELECT status FROM email_subscribers WHERE email = ? LIMIT 1");
            $subStatusStmt->execute([$to]);
            $statusRow = $subStatusStmt->fetch(PDO::FETCH_ASSOC);
            if ($statusRow && (($statusRow['status'] ?? '') === 'unsubscribed')) {
                continue;
            }
            $key = strtolower($to);
            if (!isset($recipientMap[$key])) {
                $first = trim((string) ($commenter['name'] ?? ''));
                if ($first !== '') {
                    $parts = preg_split('/\s+/', $first);
                    $first = (string) ($parts[0] ?? $first);
                }
                $recipientMap[$key] = ['email' => $to, 'first_name' => $first];
            }
        }
    } catch (Throwable $e) {
        error_log('jlm_send_new_post_updates commenters merge failed: ' . $e->getMessage());
    }

    $subscribers = array_values($recipientMap);
    $sent = 0;
    foreach ($subscribers as $subscriber) {
        $to = trim((string) ($subscriber['email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        $firstName = trim((string) ($subscriber['first_name'] ?? ''));
        $personalized = '<p style="margin-top:0;">Hi ' . htmlspecialchars($firstName !== '' ? $firstName : 'there', ENT_QUOTES, 'UTF-8') . ',</p>' . $content;
        if (sendNewsletterEmail($to, $firstName, 'New Blog Post: ' . $title, $personalized)) {
            $sent++;
        }
    }

    return $sent;
}

/**
 * Automatically publish scheduled posts whose release time has been reached.
 * Executes on page load inside any script including this helper.
 */
function jlm_publish_scheduled_posts(PDO $pdo): void {
    try {
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE status = 'scheduled' AND published_at <= ?");
        $stmt->execute([$now]);
        $scheduledPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($scheduledPosts)) {
            foreach ($scheduledPosts as $post) {
                // Concurrency safety check: only the thread that successfully updates the status
                // from 'scheduled' to 'published' is allowed to dispatch notifications.
                $updateStmt = $pdo->prepare("UPDATE blog_posts SET status = 'published' WHERE id = ? AND status = 'scheduled'");
                $updateStmt->execute([$post['id']]);
                if ($updateStmt->rowCount() > 0) {
                    $post['status'] = 'published';
                    sync_blog_social_post($pdo, $post);
                    jlm_send_new_post_updates($pdo, $post);
                }
            }
        }
    } catch (Throwable $e) {
        error_log('jlm_publish_scheduled_posts error: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/blog_schema_migrate.php';
if (($GLOBALS['pdo'] ?? null) instanceof PDO) {
    jlm_ensure_blog_posts_schema($GLOBALS['pdo']);
    jlm_publish_scheduled_posts($GLOBALS['pdo']);
}
