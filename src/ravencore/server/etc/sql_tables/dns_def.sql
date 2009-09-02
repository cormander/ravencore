CREATE TABLE dns_def (
  id integer not null primary key autoincrement unique,
  name text,
  type text,
  target text
);
