DROP TABLE IF EXISTS dns_rec;
CREATE TABLE dns_rec (
  id int(10),
  did int(10),
  name varchar(255),
  type varchar(255),
  target varchar(255)
);
