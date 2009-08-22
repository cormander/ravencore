DROP TABLE IF EXISTS error_docs;
CREATE TABLE error_docs (
  id integer not null primary key autoincrement unique,
  did int(10),
  code int(3),
  file varchar(255)
);
