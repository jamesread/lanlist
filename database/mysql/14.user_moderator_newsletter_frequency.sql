-- +migrate Up
-- Run once on existing databases (new installs pick this up from schema.sql).
ALTER TABLE users
  ADD COLUMN moderatorNewsletterFrequency varchar(16) NOT NULL DEFAULT 'daily' AFTER discordUser;
-- +migrate Down
