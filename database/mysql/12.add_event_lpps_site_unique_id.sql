-- +migrate Up
-- Track LPPS event identity per organizer for idempotent crawl imports.
-- Apply numbered scripts in order. Next migration: 13_*.sql

ALTER TABLE events
  ADD COLUMN lppsSiteUniqueId varchar(64) DEFAULT NULL AFTER postEventReminderSentAt,
  ADD KEY idx_events_organizer_lpps (organizer, lppsSiteUniqueId);
-- +migrate Down
