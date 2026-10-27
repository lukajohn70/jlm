<?php
/**
 * JLM Blog Schema Migration Runner
 * Run this by visiting: https://jlm.com.ng/run_migration.php
 * For security, please delete this file from your server after running it.
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>JLM Database Migration Runner</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f1f5f9; padding: 40px 20px; max-width: 600px; margin: 0 auto; }
        .card { background: #1e293b; border-radius: 16px; padding: 30px; border: 1px solid #334155; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        h1 { color: #f4306d; margin-top: 0; font-size: 24px; border-b: 1px solid #334155; padding-bottom: 15px; }
        .log-item { padding: 8px 12px; margin-bottom: 8px; border-radius: 8px; font-size: 14px; font-family: monospace; }
        .success { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.25); }
        .info { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.25); }
        .error { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.25); }
        .btn-delete { display: inline-block; background: #ef4444; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 20px; font-size: 14px; }
    </style>
</head>
<body>
<div class="card">
    <h1>JLM Database Migration</h1>
    <div id="logs">
        <?php
        try {
            if (!isset($pdo) || !$pdo instanceof PDO) {
                throw new Exception('Database connection ($pdo) not found in config/config.php.');
            }

            // Helper to check columns
            $columnExists = function(PDO $pdo, string $table, string $column): bool {
                $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($column));
                return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
            };

            // 1. Create blog_users table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `blog_users` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(120) NOT NULL,
                    `email` VARCHAR(190) DEFAULT NULL,
                    `password_hash` VARCHAR(255) DEFAULT NULL,
                    `oauth_provider` VARCHAR(20) DEFAULT NULL COMMENT 'google',
                    `oauth_id` VARCHAR(255) DEFAULT NULL,
                    `avatar_url` VARCHAR(255) DEFAULT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_blog_user_email` (`email`),
                    UNIQUE KEY `uq_blog_user_oauth` (`oauth_provider`, `oauth_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo '<div class="log-item success">✓ Table `blog_users` created or verified.</div>';

            // Ensure avatar_url column exists in blog_users
            if (!$columnExists($pdo, 'blog_users', 'avatar_url')) {
                $pdo->exec("ALTER TABLE `blog_users` ADD COLUMN `avatar_url` VARCHAR(255) DEFAULT NULL AFTER `oauth_id`");
                echo '<div class="log-item success">✓ Column `avatar_url` added to `blog_users`.</div>';
            } else {
                echo '<div class="log-item info">i Column `avatar_url` already exists in `blog_users`.</div>';
            }

            // 2. Add blog_user_id column to blog_post_comments
            if (!$columnExists($pdo, 'blog_post_comments', 'blog_user_id')) {
                $pdo->exec("ALTER TABLE `blog_post_comments` ADD COLUMN `blog_user_id` INT UNSIGNED DEFAULT NULL AFTER `parent_comment_id`");
                echo '<div class="log-item success">✓ Column `blog_user_id` added to `blog_post_comments`.</div>';
            } else {
                echo '<div class="log-item info">i Column `blog_user_id` already exists in `blog_post_comments`.</div>';
            }

            // 3. Add blog_user_id column to blog_post_likes
            if (!$columnExists($pdo, 'blog_post_likes', 'blog_user_id')) {
                $pdo->exec("ALTER TABLE `blog_post_likes` ADD COLUMN `blog_user_id` INT UNSIGNED DEFAULT NULL AFTER `liker_hash`");
                echo '<div class="log-item success">✓ Column `blog_user_id` added to `blog_post_likes`.</div>';
            } else {
                echo '<div class="log-item info">i Column `blog_user_id` already exists in `blog_post_likes`.</div>';
            }

            // 4. Mark all comments as approved automatically for public workflow
            $pdo->exec("UPDATE `blog_post_comments` SET `status` = 'approved' WHERE `status` = 'pending'");
            echo '<div class="log-item success">✓ All legacy comments updated to approved.</div>';

            echo '<p style="color: #34d399; font-weight: bold; margin-top: 25px;">Database migration successfully completed!</p>';
            echo '<p style="color: #ef4444; font-size: 13px;"><strong>CRITICAL:</strong> Please delete this file <code>run_migration.php</code> from your server immediately for safety.</p>';

        } catch (Throwable $e) {
            echo '<div class="log-item error">✗ Migration Failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</div>
</body>
</html>
