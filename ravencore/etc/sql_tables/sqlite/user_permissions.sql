DROP TABLE IF EXISTS user_permissions;
CREATE TABLE user_permissions (
  id int(10),
  uid int(10),
  perm varchar(255),
  val varchar(5),
  lim int(11)
);
