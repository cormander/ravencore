DROP TABLE IF EXISTS mail_users;
CREATE TABLE mail_users (
  id int(10) unsigned NOT NULL auto_increment,
  did int(10) unsigned NOT NULL default '0',
  mail_name varchar(20) default NULL,
  passwd varchar(15) default NULL,
  spamassassin enum('true','false') default 'false',
  mailbox enum('true','false') default 'false',
  spam_folder enum('true','false') default 'false',
  redirect enum('true','false') default 'false',
  redirect_addr blob,
  autoreply tinyint(1) NOT NULL default '0',
  autoreply_subject varchar(255) default NULL,
  autoreply_body blob,
  PRIMARY KEY  (id)
) TYPE=MyISAM;
/*!40000 ALTER TABLE mail_users DISABLE KEYS */;
