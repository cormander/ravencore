CREATE TABLE dns_rec (
  id integer not null primary key autoincrement unique,
  did integer,
  name text,
  type text,
  target text
);
