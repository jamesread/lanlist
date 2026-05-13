-- Run once on existing databases (new installs pick this up from schema.sql).
ALTER TABLE organizers
  ADD COLUMN faviconRefetch tinyint(1) NOT NULL DEFAULT 0 AFTER useFavicon;
