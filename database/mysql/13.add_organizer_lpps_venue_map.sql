-- +migrate Up
-- Maps LPPS venue siteUniqueId values to lanlist venues per organizer.
-- Apply numbered scripts in order. Next migration: 14_*.sql

CREATE TABLE organizer_lpps_venues (
  organizer_id int(11) NOT NULL,
  lppsVenueSiteUniqueId varchar(64) NOT NULL,
  venue_id int(11) NOT NULL,
  PRIMARY KEY (organizer_id, lppsVenueSiteUniqueId),
  KEY idx_organizer_lpps_venues_venue (venue_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
-- +migrate Down
