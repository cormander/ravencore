DROP TABLE IF EXISTS sys_users;
CREATE TABLE sys_users (
  id int(10),
  login varchar(15),
  passwd varchar(15),
  shell varchar(15),
  home_dir varchar(255)
);
