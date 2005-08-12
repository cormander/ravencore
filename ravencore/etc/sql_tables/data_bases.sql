DROP TABLE IF EXISTS data_bases;
CREATE TABLE data_bases (
  id int(10) unsigned NOT NULL auto_increment,
  did int(10) unsigned NOT NULL default '0',
  name varchar(20) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE data_bases DISABLE KEYS */;
