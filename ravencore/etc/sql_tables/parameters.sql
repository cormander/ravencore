CREATE TABLE parameters (
  id integer not null primary key autoincrement unique,
  type_id int,
  param text,
  value text
);
