-- Run once on existing databases.
ALTER TABLE logs
  ADD COLUMN relatedOrganizer int(11) DEFAULT NULL AFTER relatedUser;
