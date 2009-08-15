DROP TABLE IF EXISTS login_failure;
CREATE TABLE login_failure (
  id int(10) unsigned NOT NULL auto_increment,
  login varchar(255) NOT NULL default '',
  date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE login_failure DISABLE KEYS */;
