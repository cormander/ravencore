DROP TABLE IF EXISTS ip_addresses;
CREATE TABLE ip_addresses (
  ip_address varchar(15) NOT NULL,
  uid int(10) unsigned default NULL,
  default_did int(10) unsigned default NULL,
  active enum('true','false') NOT NULL default 'false'
) TYPE=MyISAM;
