DROP TABLE IF EXISTS sessions;
CREATE TABLE sessions (
  id int(10) unsigned NOT NULL auto_increment,
  session_id varchar(255) NOT NULL default '',
  login varchar(255) NOT NULL default '',
  location varchar(255) default NULL,
  created datetime default NULL,
  idle datetime default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE sessions DISABLE KEYS */;
