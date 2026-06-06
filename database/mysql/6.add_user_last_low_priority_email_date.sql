-- +migrate Up
-- Migration 6: throttle low-priority notification emails (event updated) per user.
-- Apply after migration 5. New installs pick up from schema.sql. Next migration: 7_*.sql
ALTER TABLE users
  ADD COLUMN organizerUpdateEmails varchar(16) NOT NULL DEFAULT 'always',
  ADD COLUMN eventUpdateEmails varchar(16) NOT NULL DEFAULT 'always' AFTER organizerUpdateEmails,
  ADD COLUMN lastLowPriorityEmailDate datetime DEFAULT NULL AFTER eventUpdateEmails;
-- +migrate Down
