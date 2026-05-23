-- Run once on existing databases (new installs pick this up from schema.sql).
ALTER TABLE users
  ADD COLUMN organizerUpdateEmails varchar(16) NOT NULL DEFAULT 'always' AFTER moderatorNewsletterFrequency,
  ADD COLUMN eventUpdateEmails varchar(16) NOT NULL DEFAULT 'always' AFTER organizerUpdateEmails;
