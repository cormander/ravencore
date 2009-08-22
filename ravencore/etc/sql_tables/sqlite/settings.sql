DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
  id integer not null primary key autoincrement unique,
  setting varchar(255),
  value varchar(255)
);
