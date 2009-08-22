DROP TABLE IF EXISTS login_failure;
CREATE TABLE login_failure (
  id integer not null primary key autoincrement unique,
  login varchar(255),
  date varchar(10)
);
