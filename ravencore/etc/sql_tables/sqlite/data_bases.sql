DROP TABLE IF EXISTS data_bases;
CREATE TABLE data_bases (
  id integer not null primary key autoincrement unique,
  did int(10),
  name varchar(20)
);
