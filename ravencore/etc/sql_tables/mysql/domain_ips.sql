DROP TABLE IF EXISTS domain_ips;
CREATE TABLE domain_ips (
  did int(10) unsigned NOT NULL,
  ip_address varchar(15) NOT NULL
) TYPE=MyISAM;
