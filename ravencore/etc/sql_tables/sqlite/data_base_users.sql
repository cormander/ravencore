DROP TABLE IF EXISTS data_base_users;
CREATE TABLE data_base_users (
  id integer not null primary key autoincrement unique,
  login varchar(15),
  passwd varchar(15),
  db_id int(10)
);
