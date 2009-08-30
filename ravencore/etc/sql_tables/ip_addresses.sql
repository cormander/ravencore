CREATE TABLE ip_addresses (
  ip_address text not null primary key unique,
  uid integer,
  default_did integer,
  active text
);
