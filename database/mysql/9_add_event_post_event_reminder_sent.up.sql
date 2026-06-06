-- Track post-event reminder emails so the daily job does not send duplicates.
ALTER TABLE events
  ADD COLUMN postEventReminderSentAt datetime DEFAULT NULL AFTER ticketsNotReleasedUntil;
