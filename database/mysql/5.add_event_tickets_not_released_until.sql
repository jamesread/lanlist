-- +migrate Up
-- Migration 5: tickets-not-yet-released silence for site checks.
-- Apply numbered scripts in order on existing deployments (legacy unnumbered scripts precede 5).
-- New installs pick up this column from schema.sql. Next migration: 6_*.sql
ALTER TABLE events
  ADD COLUMN ticketsNotReleasedUntil datetime DEFAULT NULL AFTER ageRestrictions;
-- +migrate Down
