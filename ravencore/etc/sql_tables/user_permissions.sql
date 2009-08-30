CREATE TABLE user_permissions (
  id integer not null primary key autoincrement unique,
  uid int,
  perm text,
  val text,
  lim int
);
