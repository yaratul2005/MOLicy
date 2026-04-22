-- Migration 0001: Phase 4 — User System Expansion + Forum Engine Tables
-- Adds: user profile fields, notifications, votes, tags, reactions, badges, media

-- -------------------------------------------------------
-- Expand users table
-- -------------------------------------------------------
ALTER TABLE `users`
    ADD COLUMN `avatar`       VARCHAR(255)  NULL AFTER `email`,
    ADD COLUMN `bio`          TEXT          NULL AFTER `avatar`,
    ADD COLUMN `website`      VARCHAR(255)  NULL AFTER `bio`,
    ADD COLUMN `location`     VARCHAR(100)  NULL AFTER `website`,
    ADD COLUMN `role`         ENUM('member','moderator','admin') NOT NULL DEFAULT 'member' AFTER `location`,
    ADD COLUMN `2fa_secret`   VARCHAR(64)   NULL AFTER `role`,
    ADD COLUMN `oauth_provider` VARCHAR(30) NULL AFTER `2fa_secret`,
    ADD COLUMN `oauth_id`     VARCHAR(100)  NULL AFTER `oauth_provider`,
    ADD COLUMN `email_verified_at` TIMESTAMP NULL AFTER `oauth_id`,
    ADD COLUMN `last_seen_at` TIMESTAMP     NULL AFTER `email_verified_at`,
    ADD COLUMN `post_count`   INT UNSIGNED  NOT NULL DEFAULT 0 AFTER `last_seen_at`,
    ADD COLUMN `membership_tier` TINYINT   NOT NULL DEFAULT 0 AFTER `post_count`;

-- -------------------------------------------------------
-- Thread type + extra fields
-- -------------------------------------------------------
ALTER TABLE `threads`
    ADD COLUMN `type`          ENUM('discussion','question','announcement','poll','showcase','debate') NOT NULL DEFAULT 'discussion' AFTER `slug`,
    ADD COLUMN `last_post_at`  TIMESTAMP NULL AFTER `type`,
    ADD COLUMN `reply_count`   INT UNSIGNED NOT NULL DEFAULT 0 AFTER `last_post_at`,
    ADD COLUMN `is_solved`     BOOLEAN NOT NULL DEFAULT FALSE AFTER `reply_count`,
    ADD FULLTEXT INDEX `ft_title` (`title`);

-- -------------------------------------------------------
-- Posts full-text index
-- -------------------------------------------------------
ALTER TABLE `posts`
    ADD FULLTEXT INDEX `ft_content` (`content`);

-- -------------------------------------------------------
-- Votes table (threads & posts)
-- -------------------------------------------------------
CREATE TABLE `votes` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `votable_type` ENUM('thread','post') NOT NULL,
    `votable_id` BIGINT UNSIGNED NOT NULL,
    `value`      TINYINT NOT NULL DEFAULT 1 COMMENT '1 = up, -1 = down',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_vote` (`user_id`, `votable_type`, `votable_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Reactions (emoji) on posts
-- -------------------------------------------------------
CREATE TABLE `reactions` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `post_id`    BIGINT UNSIGNED NOT NULL,
    `emoji`      VARCHAR(10) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_reaction` (`user_id`, `post_id`, `emoji`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`post_id`)  REFERENCES `posts`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Tags
-- -------------------------------------------------------
CREATE TABLE `tags` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(50) NOT NULL UNIQUE,
    `slug`       VARCHAR(60) NOT NULL UNIQUE,
    `color`      VARCHAR(7)  NULL COMMENT 'Hex color',
    `thread_count` INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `thread_tags` (
    `thread_id` BIGINT UNSIGNED NOT NULL,
    `tag_id`    INT UNSIGNED NOT NULL,
    PRIMARY KEY (`thread_id`, `tag_id`),
    FOREIGN KEY (`thread_id`) REFERENCES `threads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`)    REFERENCES `tags`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Notifications
-- -------------------------------------------------------
CREATE TABLE `notifications` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `type`        VARCHAR(50) NOT NULL COMMENT 'e.g. new_reply, mention, vote, badge',
    `data`        JSON NOT NULL,
    `is_read`     BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notif_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Badges
-- -------------------------------------------------------
CREATE TABLE `badges` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(80) NOT NULL UNIQUE,
    `slug`        VARCHAR(80) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon`        VARCHAR(100) NULL,
    `tier`        TINYINT NOT NULL DEFAULT 1 COMMENT '1=bronze,2=silver,3=gold'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_badges` (
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `badge_id`   INT UNSIGNED NOT NULL,
    `awarded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `badge_id`),
    FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`badge_id`) REFERENCES `badges`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Media uploads
-- -------------------------------------------------------
CREATE TABLE `media` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `filename`    VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `mime_type`   VARCHAR(80)  NOT NULL,
    `size`        INT UNSIGNED NOT NULL,
    `path`        VARCHAR(500) NOT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Polls (for thread type = poll)
-- -------------------------------------------------------
CREATE TABLE `polls` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `thread_id`   BIGINT UNSIGNED NOT NULL UNIQUE,
    `question`    VARCHAR(255) NOT NULL,
    `closes_at`   TIMESTAMP NULL,
    FOREIGN KEY (`thread_id`) REFERENCES `threads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `poll_options` (
    `id`       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `poll_id`  INT UNSIGNED NOT NULL,
    `text`     VARCHAR(255) NOT NULL,
    `votes`    INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (`poll_id`) REFERENCES `polls`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `poll_votes` (
    `user_id`   BIGINT UNSIGNED NOT NULL,
    `poll_id`   INT UNSIGNED NOT NULL,
    `option_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `poll_id`),
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)        ON DELETE CASCADE,
    FOREIGN KEY (`option_id`) REFERENCES `poll_options`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Mentions
-- -------------------------------------------------------
CREATE TABLE `mentions` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id`     BIGINT UNSIGNED NOT NULL,
    `mentioned_user_id` BIGINT UNSIGNED NOT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_mention` (`post_id`, `mentioned_user_id`),
    FOREIGN KEY (`post_id`)             REFERENCES `posts`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`mentioned_user_id`)   REFERENCES `users`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- SSE event queue (real-time notifications)
-- -------------------------------------------------------
CREATE TABLE `sse_events` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `payload`    JSON NOT NULL,
    `dispatched` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sse_pending` (`user_id`, `dispatched`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Default badges
-- -------------------------------------------------------
INSERT INTO `badges` (`name`, `slug`, `description`, `icon`, `tier`) VALUES
('First Post',     'first-post',     'Made your first post',              '✍️', 1),
('Popular Thread', 'popular-thread', 'Thread reached 100 views',          '🔥', 2),
('Helpful',        'helpful',        'Answer marked as best answer',       '💡', 2),
('Veteran',        'veteran',        'Member for over 1 year',             '⭐', 3),
('Elder',          'elder',          'Reached trust level 5',              '👑', 3);

-- -------------------------------------------------------
-- ACP sessions (admin separate session tracking)
-- -------------------------------------------------------
CREATE TABLE `admin_sessions` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `ip`         VARCHAR(45) NOT NULL,
    `token`      VARCHAR(64) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
