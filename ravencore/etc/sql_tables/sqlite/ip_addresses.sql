DROP TABLE IF EXISTS ip_addresses;
CREATE TABLE ip_addresses (
  ip_address varchar(15),
  uid int(10),
  default_did int(10),
  active varchar(5)
);