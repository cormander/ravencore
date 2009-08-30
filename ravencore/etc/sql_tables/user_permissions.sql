CREATE TABLE user_permissions (
  id integer not null primary key autoincrement unique,
  uid integer,
  perm text,
  val text,
  lim integer
);
