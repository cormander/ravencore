DROP TABLE IF EXISTS domain_traffic;
CREATE TABLE domain_traffic (
  id int(10) unsigned NOT NULL auto_increment,
  did int(10) unsigned NOT NULL default '0',
  type enum('ftp','web','mail') NOT NULL default 'ftp',
  bytes int(10) unsigned NOT NULL default '0',
  date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE domain_traffic DISABLE KEYS */;
