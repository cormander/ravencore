CREATE TABLE users (
  id integer not null primary key autoincrement unique,
  created int,
  company text,
  name text,
  login text,
  passwd text,
  email text,
  space_limit int,
  traffic_limit int
);
