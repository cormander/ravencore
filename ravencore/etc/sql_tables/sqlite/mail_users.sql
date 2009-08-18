DROP TABLE IF EXISTS mail_users;
CREATE TABLE mail_users (
  id int(10),
  did int(10),
  mail_name varchar(20),
  passwd varchar(15),
  spamassassin varchar(5),
  mailbox varchar(5),
  spam_folder varchar(5),
  redirect varchar(5),
  redirect_addr varchar(255),
  autoreply int(1),
  autoreply_subject varchar(255),
  autoreply_body varchar(255)
);
