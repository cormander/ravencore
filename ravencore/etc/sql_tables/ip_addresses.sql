DROP TABLE IF EXISTS ip_addresses;
CREATE TABLE ip_addresses (
  ip_address varchar(15) NOT NULL,
  uid int(10) unsigned NULL,
  default_did int(10) unsigned NULL,
  active enum('true','false') NOT NULL default 'false'
) TYPE=MyISAM;
