CREATE TABLE parameters (
  id integer not null primary key autoincrement unique,
  type_id integer,
  param text,
  value text
);
