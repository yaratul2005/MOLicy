-- Migration 0003: Phase 11 — Email Verification
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `verification_token` VARCHAR(64) NULL AFTER `password`,
    ADD COLUMN IF NOT EXISTS `is_verified` BOOLEAN NOT NULL DEFAULT FALSE AFTER `verification_token`;
