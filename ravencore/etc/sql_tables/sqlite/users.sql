DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id int(10),
  created varchar(10),
  company varchar(80),
  name varchar(80),
  login varchar(15),
  passwd varchar(15),
  email varchar(120),
  space_limit int(11),
  traffic_limit int(11)
);
