-- +migrate Up
-- Per-user throttle: at most one post-event reminder email per rolling month.
ALTER TABLE users
  ADD COLUMN lastPostEventReminderEmailDate datetime DEFAULT NULL AFTER lastLowPriorityEmailDate;
-- +migrate Down
