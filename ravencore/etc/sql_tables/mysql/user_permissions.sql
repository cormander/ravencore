DROP TABLE IF EXISTS user_permissions;
CREATE TABLE user_permissions (
  id int(10) unsigned NOT NULL auto_increment,
  uid int(10) unsigned NOT NULL default '0',
  perm varchar(255) NOT NULL default '',
  val enum('yes','no') NOT NULL default 'no',
  lim int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE user_permissions DISABLE KEYS */;
