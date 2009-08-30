CREATE TABLE domain_space (
  id integer not null primary key autoincrement unique,
  did int,
  type text,
  date int,
  bytes int
);
