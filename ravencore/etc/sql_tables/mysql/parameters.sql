DROP TABLE IF EXISTS parameters;
CREATE TABLE parameters (
  id int(10) unsigned NOT NULL auto_increment,
  type_id int(10) unsigned NOT NULL default '0',
  param varchar(255) NOT NULL default '',
  value varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY param (param)
) TYPE=MyISAM;
/*!40000 ALTER TABLE parameters DISABLE KEYS */;
