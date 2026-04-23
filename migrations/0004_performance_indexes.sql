-- Migration 0004: Performance & Stability
-- Adds indexes to speed up thread/post lookups and adds missing columns if any

-- ── Indexes for threads ───────────────────────────────────────────
ALTER TABLE `threads`
    ADD INDEX IF NOT EXISTS `idx_threads_cat` (`category_id`),
    ADD INDEX IF NOT EXISTS `idx_threads_user` (`user_id`),
    ADD INDEX IF NOT EXISTS `idx_threads_created` (`created_at`),
    ADD INDEX IF NOT EXISTS `idx_threads_last_post` (`last_post_at`);

-- ── Indexes for posts ─────────────────────────────────────────────
ALTER TABLE `posts`
    ADD INDEX IF NOT EXISTS `idx_posts_thread` (`thread_id`),
    ADD INDEX IF NOT EXISTS `idx_posts_user` (`user_id`),
    ADD INDEX IF NOT EXISTS `idx_posts_created` (`created_at`);

-- ── Index for notifications ───────────────────────────────────────
ALTER TABLE `notifications`
    ADD INDEX IF NOT EXISTS `idx_notif_created` (`created_at`);

-- ── Ensure all engines are InnoDB ──────────────────────────────────
ALTER TABLE `users` ENGINE=InnoDB;
ALTER TABLE `threads` ENGINE=InnoDB;
ALTER TABLE `posts` ENGINE=InnoDB;
ALTER TABLE `categories` ENGINE=InnoDB;
ALTER TABLE `settings` ENGINE=InnoDB;
