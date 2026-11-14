-- Reversible down migration for 20261105000014_create_auth_security_tables.sql

DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `system_settings`;
ALTER TABLE `users` DROP COLUMN IF EXISTS `auth_version`;
