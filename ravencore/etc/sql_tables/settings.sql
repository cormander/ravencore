DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
  id int(10) unsigned NOT NULL auto_increment,
  setting varchar(255) NOT NULL default '',
  value varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE settings DISABLE KEYS */;
