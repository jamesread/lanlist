-- Migration 8: track whether an organizer has a valid uploaded banner.
-- Apply numbered scripts in order on existing deployments. Next migration: 9_*.sql
-- After applying, run: php scripts/backfill_organizer_validBanner.php

ALTER TABLE organizers
  ADD COLUMN validBanner tinyint(1) NOT NULL DEFAULT 0 AFTER faviconRefetch;
