DROP TABLE IF EXISTS domain_space;
CREATE TABLE domain_space (
  id int(10) unsigned NOT NULL auto_increment,
  did int(10) unsigned NOT NULL default '0',
  type enum('web','database','mail') NOT NULL default 'web',
  bytes int(10) unsigned NOT NULL default '0',
  date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE domain_space DISABLE KEYS */;
