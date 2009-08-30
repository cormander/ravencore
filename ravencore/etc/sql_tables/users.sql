CREATE TABLE users (
  id integer not null primary key autoincrement unique,
  created integer,
  company text,
  name text,
  login text,
  passwd text,
  email text,
  space_limit integer,
  traffic_limit integer
);
