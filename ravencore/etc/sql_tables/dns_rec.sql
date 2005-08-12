DROP TABLE IF EXISTS dns_rec;
CREATE TABLE dns_rec (
  id int(10) unsigned NOT NULL auto_increment,
  did int(10) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  type varchar(255) NOT NULL default '',
  target varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE dns_rec DISABLE KEYS */;
