CREATE TABLE sys_users (
  id integer not null primary key autoincrement unique,
  login text,
  passwd text,
  shell text,
  home_dir text
);
