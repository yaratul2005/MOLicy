-- Migration 0002: Phase 9 — Settings Expansion + Forum Features
-- Adds: full settings columns, categories icon field, members directory, flags table

-- ── Settings defaults ──────────────────────────────────────────────
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_title',           'AntiGravity Forum'),
('site_tagline',         'The most beautiful open-canvas knowledge forum.'),
('site_logo',            ''),
('site_favicon',         ''),
('site_theme',           'antigravity'),
('site_language',        'en'),
('site_timezone',        'UTC'),
('registration_enabled', '1'),
('require_email_verify', '0'),
('allow_guest_view',     '1'),
('posts_per_page',       '20'),
('threads_per_page',     '20'),
('max_upload_size',      '5'),
('allowed_file_types',   'jpg,png,gif,webp,pdf,zip'),
('maintenance_mode',     '0'),
('maintenance_message',  'We are performing scheduled maintenance. Please check back soon.'),
('custom_css',           ''),
('custom_js',            ''),
('footer_text',          'Powered by AntiGravity Forum'),
('google_analytics',     ''),
('meta_description',     'The most beautiful open-canvas knowledge forum.'),
('og_image',             ''),
('canonical_domain',     ''),
('robots_txt',           "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /api/"),
('noindex_search',       '0'),
('forum_email',          ''),
('smtp_host',            ''),
('smtp_port',            '587'),
('smtp_user',            ''),
('smtp_pass',            ''),
('smtp_secure',          'tls'),
('home_welcome_enabled', '1'),
('home_welcome_title',   'Welcome to Our Forum'),
('home_welcome_text',    'Join the discussion and discover new ideas.');

-- ── Add icon field to categories ────────────────────────────────────
ALTER TABLE `categories`
    ADD COLUMN IF NOT EXISTS `icon`         VARCHAR(10)   NULL DEFAULT '💬' AFTER `name`,
    ADD COLUMN IF NOT EXISTS `color`        VARCHAR(7)    NULL DEFAULT '#7c3aed' AFTER `icon`,
    ADD COLUMN IF NOT EXISTS `thread_count` INT UNSIGNED  NOT NULL DEFAULT 0 AFTER `color`,
    ADD COLUMN IF NOT EXISTS `is_private`   BOOLEAN       NOT NULL DEFAULT FALSE AFTER `thread_count`;

-- ── Flags/Reports table ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `flags` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id`    BIGINT UNSIGNED NOT NULL,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `reason`     VARCHAR(100) NOT NULL DEFAULT 'spam',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_flag` (`post_id`, `user_id`),
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Bookmarks ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `bookmarks` (
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `thread_id`  BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `thread_id`),
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`thread_id`) REFERENCES `threads`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Thread Subscriptions ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `thread_subscriptions` (
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `thread_id`  BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `thread_id`),
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`thread_id`) REFERENCES `threads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Audit Log ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id`   BIGINT UNSIGNED NULL,
    `action`     VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) NULL,
    `target_id`  BIGINT UNSIGNED NULL,
    `details`    JSON NULL,
    `ip`         VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_admin` (`admin_id`),
    INDEX `idx_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
