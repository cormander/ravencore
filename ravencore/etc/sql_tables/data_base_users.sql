CREATE TABLE data_base_users (
  id integer not null primary key autoincrement unique,
  login text,
  passwd text,
  db_id int
);
