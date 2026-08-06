-- +migrate Up
-- Allow Unicode in audit log content (e.g. → in publish-toggle messages).
ALTER TABLE logs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- +migrate Down
ALTER TABLE logs CONVERT TO CHARACTER SET latin1;
