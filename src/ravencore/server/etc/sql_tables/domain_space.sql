CREATE TABLE domain_space (
  id integer not null primary key autoincrement unique,
  did integer,
  type text,
  date integer,
  bytes integer
);
