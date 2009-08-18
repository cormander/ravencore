DROP TABLE IF EXISTS error_docs;
CREATE TABLE error_docs (
  id int(10) unsigned NOT NULL auto_increment,
  did int(10) unsigned NOT NULL default '0',
  code int(3) default NULL,
  file varchar(255) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE error_docs DISABLE KEYS */;
