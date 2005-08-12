DROP TABLE IF EXISTS data_base_users;
CREATE TABLE data_base_users (
  id int(10) unsigned NOT NULL auto_increment,
  login varchar(15) default NULL,
  passwd varchar(15) default NULL,
  db_id int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE data_base_users DISABLE KEYS */;
