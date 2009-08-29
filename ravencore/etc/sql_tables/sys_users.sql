CREATE TABLE sys_users (
  id integer not null primary key autoincrement unique,
  login varchar(15),
  passwd varchar(15),
  shell varchar(15),
  home_dir varchar(255)
);
