CREATE TABLE dns_def (
  id integer not null primary key autoincrement unique,
  name varchar(255),
  type varchar(255),
  target varchar(255)
);
