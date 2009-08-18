DROP TABLE IF EXISTS dns_def;
CREATE TABLE dns_def (
  id int(10) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  type varchar(255) NOT NULL default '',
  target varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE dns_def DISABLE KEYS */;
