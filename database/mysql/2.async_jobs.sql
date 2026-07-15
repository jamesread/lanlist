-- +migrate Up
-- Run once on existing databases (new installs pick this up from schema.sql).
CREATE TABLE IF NOT EXISTS `async_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_type` varchar(64) NOT NULL,
  `organizer_id` int(11) DEFAULT NULL,
  `status` varchar(32) NOT NULL,
  `execution_tracking_id` varchar(255) DEFAULT NULL,
  `metadata` json NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_async_jobs_type_org_status` (`job_type`, `organizer_id`, `status`),
  KEY `idx_async_jobs_type_status_id` (`job_type`, `status`, `id`),
  KEY `idx_async_jobs_execution_tracking_id` (`execution_tracking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- +migrate Down
