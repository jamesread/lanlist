-- Migration 7: useful related sites (DB-backed usefulRelatedSites.php).
-- Apply numbered scripts in order on existing deployments. Next migration: 8_*.sql

CREATE TABLE useful_related_sites (
  id int(11) NOT NULL AUTO_INCREMENT,
  url varchar(512) NOT NULL,
  title varchar(128) NOT NULL,
  description varchar(1024) NOT NULL DEFAULT '',
  sortOrder int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE useful_related_site_countries (
  site_id int(11) NOT NULL,
  country varchar(24) NOT NULL,
  PRIMARY KEY (site_id, country)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO permissions (`key`, description)
SELECT 'MANAGE_LINKS', 'Manage useful related site links'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE `key` = 'MANAGE_LINKS');

INSERT INTO useful_related_sites (url, title, description, sortOrder) VALUES
('https://lan.party', 'lan.party', 'A site created in April 2024, hosting resources for the LAN party community.', 10),
('https://lanparty.dk', 'lanparty.dk', 'A community and event list for LAN parties in and near Denmark.', 20),
('https://www.dot-lan.at/map', 'dot-lan.at/map', 'A community map for the DoT LAN Party (Austria), showing where members connect from.', 30),
('https://landb.no/kart', 'landb.no/kart', 'A map and event list for LAN parties in Norway.', 40),
('http://www.lanpartywiki.net', 'LANPartyWiki', 'A wikipedia-like wiki for all things related to LAN Parties.', 50);

INSERT INTO useful_related_site_countries (site_id, country)
SELECT id, 'Denmark' FROM useful_related_sites WHERE title = 'lanparty.dk';

INSERT INTO useful_related_site_countries (site_id, country)
SELECT id, 'Austria' FROM useful_related_sites WHERE title = 'dot-lan.at/map';

INSERT INTO useful_related_site_countries (site_id, country)
SELECT id, 'Norway' FROM useful_related_sites WHERE title = 'landb.no/kart';
