-- Migration 0004: Phase 12 — Anti-Spam & reCAPTCHA Settings
-- Adds default settings for the reCAPTCHA integration

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('enable_recaptcha', '0'),
('recaptcha_site_key', ''),
('recaptcha_secret_key', '');
