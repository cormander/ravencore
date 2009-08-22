DROP TABLE IF EXISTS parameters;
CREATE TABLE parameters (
  id integer not null primary key autoincrement unique,
  type_id int(10),
  param varchar(255),
  value varchar(255)
);
