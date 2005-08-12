DROP TABLE IF EXISTS sys_users;
CREATE TABLE sys_users (
  id int(10) unsigned NOT NULL auto_increment,
  login varchar(15) default NULL,
  passwd varchar(15) default NULL,
  shell varchar(15) NOT NULL default '/sbin/nologin',
  home_dir varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE sys_users DISABLE KEYS */;
