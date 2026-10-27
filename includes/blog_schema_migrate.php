<?php
/**
 * Add blog_posts columns/indexes expected by the admin editor and public blog.
 * Safe to run repeatedly. Keeps local DBs in sync without re-running full setup_database.php.
 */

declare(strict_types=1);

function jlm_blog_schema_column_exists(PDO $pdo, string $table, string $column): bool {
    $quoted = $pdo->quote($column);
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE {$quoted}");
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function jlm_blog_schema_index_exists(PDO $pdo, string $table, string $indexName): bool {
    $quoted = $pdo->quote($indexName);
    $stmt = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = {$quoted}");
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function jlm_blog_schema_ensure_column(PDO $pdo, string $table, string $column, string $definition): void {
    if (!jlm_blog_schema_column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function jlm_blog_schema_ensure_index(PDO $pdo, string $table, string $indexName, string $sql): void {
    if (!jlm_blog_schema_index_exists($pdo, $table, $indexName)) {
        $pdo->exec($sql);
    }
}

function jlm_ensure_blog_posts_schema(?PDO $pdo): void {
    static $done = false;
    if ($done || !$pdo instanceof PDO) {
        return;
    }

    $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote('blog_posts'));
    if (!$stmt || !$stmt->fetch(PDO::FETCH_NUM)) {
        return;
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `blog_post_comments` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `blog_post_id` INT UNSIGNED NOT NULL,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(190) DEFAULT NULL,
                `comment` TEXT NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'approved',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_blog_post_comments_post_status` (`blog_post_id`, `status`),
                KEY `idx_blog_post_comments_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `blog_post_likes` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `blog_post_id` INT UNSIGNED NOT NULL,
                `liker_hash` CHAR(64) NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_blog_like_once` (`blog_post_id`, `liker_hash`),
                KEY `idx_blog_post_likes_post` (`blog_post_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `blog_comment_reactions` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `comment_id` INT UNSIGNED NOT NULL,
                `reactor_hash` CHAR(64) NOT NULL,
                `reaction` VARCHAR(20) NOT NULL DEFAULT 'like',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_comment_reaction_once` (`comment_id`, `reactor_hash`, `reaction`),
                KEY `idx_comment_reactions_comment` (`comment_id`),
                KEY `idx_comment_reactions_type` (`reaction`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'image', "VARCHAR(500) DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'excerpt', "TEXT DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'published_at', "DATETIME DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'category', "VARCHAR(120) DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'tags', "TEXT DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'meta_title', "VARCHAR(255) DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'meta_description', "VARCHAR(320) DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'og_image', "VARCHAR(500) DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'canonical_url', "VARCHAR(500) DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'is_featured', "TINYINT(1) NOT NULL DEFAULT 0");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'is_pinned', "TINYINT(1) NOT NULL DEFAULT 0");
        jlm_blog_schema_ensure_column($pdo, 'blog_posts', 'reading_time_minutes', "INT NOT NULL DEFAULT 0");
        jlm_blog_schema_ensure_column($pdo, 'blog_post_comments', 'status', "VARCHAR(20) NOT NULL DEFAULT 'approved'");
        jlm_blog_schema_ensure_column($pdo, 'blog_post_comments', 'parent_comment_id', "INT UNSIGNED DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_post_comments', 'is_author_reply', "TINYINT(1) NOT NULL DEFAULT 0");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `blog_email_notifications` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `blog_post_id` INT UNSIGNED NOT NULL,
                `notification_type` VARCHAR(50) NOT NULL DEFAULT 'new_post',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_blog_email_once` (`blog_post_id`, `notification_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        jlm_blog_schema_ensure_index($pdo, 'blog_posts', 'idx_blog_category', "ALTER TABLE `blog_posts` ADD INDEX `idx_blog_category` (`category`)");
        jlm_blog_schema_ensure_index($pdo, 'blog_posts', 'idx_blog_featured', "ALTER TABLE `blog_posts` ADD INDEX `idx_blog_featured` (`is_featured`)");
        jlm_blog_schema_ensure_index($pdo, 'blog_posts', 'idx_blog_pinned', "ALTER TABLE `blog_posts` ADD INDEX `idx_blog_pinned` (`is_pinned`)");
        jlm_blog_schema_ensure_index($pdo, 'blog_post_comments', 'idx_blog_post_comments_post_status', "ALTER TABLE `blog_post_comments` ADD INDEX `idx_blog_post_comments_post_status` (`blog_post_id`, `status`)");
        jlm_blog_schema_ensure_index($pdo, 'blog_post_comments', 'idx_blog_post_comments_created_at', "ALTER TABLE `blog_post_comments` ADD INDEX `idx_blog_post_comments_created_at` (`created_at`)");
        jlm_blog_schema_ensure_index($pdo, 'blog_post_comments', 'idx_blog_post_comments_parent', "ALTER TABLE `blog_post_comments` ADD INDEX `idx_blog_post_comments_parent` (`parent_comment_id`)");
        jlm_blog_schema_ensure_index($pdo, 'blog_post_likes', 'uq_blog_like_once', "ALTER TABLE `blog_post_likes` ADD UNIQUE INDEX `uq_blog_like_once` (`blog_post_id`, `liker_hash`)");
        jlm_blog_schema_ensure_index($pdo, 'blog_comment_reactions', 'uq_comment_reaction_once', "ALTER TABLE `blog_comment_reactions` ADD UNIQUE INDEX `uq_comment_reaction_once` (`comment_id`, `reactor_hash`, `reaction`)");
        jlm_blog_schema_ensure_index($pdo, 'blog_comment_reactions', 'idx_comment_reactions_comment', "ALTER TABLE `blog_comment_reactions` ADD INDEX `idx_comment_reactions_comment` (`comment_id`)");
        jlm_blog_schema_ensure_index($pdo, 'blog_comment_reactions', 'idx_comment_reactions_type', "ALTER TABLE `blog_comment_reactions` ADD INDEX `idx_comment_reactions_type` (`reaction`)");
        jlm_blog_schema_ensure_index($pdo, 'blog_email_notifications', 'uq_blog_email_once', "ALTER TABLE `blog_email_notifications` ADD UNIQUE INDEX `uq_blog_email_once` (`blog_post_id`, `notification_type`)");

        // --- Blog User Account System (for public commenters/likers) ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `blog_users` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(190) NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_blog_user_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        jlm_blog_schema_ensure_column($pdo, 'blog_post_comments', 'blog_user_id', "INT UNSIGNED DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_post_likes', 'blog_user_id', "INT UNSIGNED DEFAULT NULL");

        // --- OAuth / Social login columns ---
        jlm_blog_schema_ensure_column($pdo, 'blog_users', 'oauth_provider', "VARCHAR(20) DEFAULT NULL COMMENT 'google|facebook|apple'");
        jlm_blog_schema_ensure_column($pdo, 'blog_users', 'oauth_id', "VARCHAR(255) DEFAULT NULL");
        jlm_blog_schema_ensure_column($pdo, 'blog_users', 'avatar_url', "VARCHAR(255) DEFAULT NULL");
        jlm_blog_schema_ensure_index($pdo, 'blog_users', 'uq_blog_user_oauth',
            "ALTER TABLE `blog_users` ADD UNIQUE INDEX `uq_blog_user_oauth` (`oauth_provider`, `oauth_id`)"
        );

        // Make password_hash nullable so OAuth-only accounts don't need one
        $phRow = $pdo->query(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'blog_users'
               AND COLUMN_NAME  = 'password_hash'"
        )->fetch(PDO::FETCH_ASSOC);
        if ($phRow && $phRow['IS_NULLABLE'] === 'NO') {
            $pdo->exec("ALTER TABLE `blog_users` MODIFY COLUMN `password_hash` VARCHAR(255) DEFAULT NULL");
        }

        // Make email nullable so Apple-only accounts without email disclosure work
        $emailRow = $pdo->query(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'blog_users'
               AND COLUMN_NAME  = 'email'"
        )->fetch(PDO::FETCH_ASSOC);
        if ($emailRow && $emailRow['IS_NULLABLE'] === 'NO') {
            $pdo->exec("ALTER TABLE `blog_users` MODIFY COLUMN `email` VARCHAR(190) DEFAULT NULL");
        }

        // Move legacy pending comments to approved for instant-public workflow.
        $pdo->exec("UPDATE `blog_post_comments` SET `status` = 'approved' WHERE `status` = 'pending'");

        // --- OTP table for manual blog registration email verification ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `blog_user_otp` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `email` VARCHAR(190) NOT NULL,
                `name` VARCHAR(120) NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `otp_code` CHAR(6) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_blog_otp_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $done = true;
    } catch (Throwable $e) {
        error_log('jlm_ensure_blog_posts_schema: ' . $e->getMessage());
    }
}
