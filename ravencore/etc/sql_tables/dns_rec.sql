CREATE TABLE dns_rec (
  id integer not null primary key autoincrement unique,
  did int,
  name text,
  type text,
  target text
);
