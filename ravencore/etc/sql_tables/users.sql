DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id int(10) unsigned NOT NULL auto_increment,
  created date NOT NULL default '0000-00-00',
  company varchar(80) default NULL,
  name varchar(80) default NULL,
  login varchar(15) default NULL,
  passwd varchar(15) default NULL,
  email varchar(120) default NULL,
  space_limit int(11) NOT NULL default '0',
  traffic_limit int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE users DISABLE KEYS */;
