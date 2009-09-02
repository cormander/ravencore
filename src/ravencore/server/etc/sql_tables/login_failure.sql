CREATE TABLE login_failure (
  id integer not null primary key autoincrement unique,
  login text,
  date integer
);
