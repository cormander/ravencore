DROP TABLE IF EXISTS domain_space;
CREATE TABLE domain_space (
  id integer not null primary key autoincrement unique,
  did int(10),
  type varchar(10),
  bytes int(10),
  varchar datetime(10)
);
