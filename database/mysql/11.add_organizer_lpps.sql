-- +migrate Up
-- LPPS (Lan Party Publishing Standard) fields for organizer syndication crawls.
-- Apply numbered scripts in order on existing deployments. Next migration: 12_*.sql
-- Spec: https://github.com/jamesread/lan-party-publishing-standard

ALTER TABLE organizers
  ADD COLUMN lppsUrl varchar(512) DEFAULT NULL AFTER validBanner,
  ADD COLUMN lppsLastCrawl datetime DEFAULT NULL AFTER lppsUrl,
  ADD COLUMN lppsCrawlSuccess tinyint(1) DEFAULT NULL AFTER lppsLastCrawl,
  ADD COLUMN lppsCrawlResult varchar(1024) DEFAULT NULL AFTER lppsCrawlSuccess,
  ADD COLUMN lppsAdminDisabled tinyint(1) NOT NULL DEFAULT 0 AFTER lppsCrawlResult;
-- +migrate Down
