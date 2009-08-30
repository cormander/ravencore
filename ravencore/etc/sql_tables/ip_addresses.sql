CREATE TABLE ip_addresses (
  ip_address text not null primary key unique,
  uid int,
  default_did int,
  active text
);
