CREATE TABLE dns_rec (
  id integer not null primary key autoincrement unique,
  did int(10),
  name varchar(255),
  type varchar(255),
  target varchar(255)
);
