CREATE TABLE mail_users (
  id integer not null primary key autoincrement unique,
  did integer,
  mail_name text,
  passwd text,
  spamassassin text,
  mailbox text,
  spam_folder text,
  redirect text,
  redirect_addr text,
  autoreply integer,
  autoreply_subject text,
  autoreply_body text
);
