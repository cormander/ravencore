CREATE TABLE domains (
  id integer not null primary key autoincrement unique,
  created integer,
  name text,
  subdomain integer,
  uid integer,
  host_type text,
  hosting text,
  redirect_url text,
  catchall text,
  catchall_addr text,
  bounce_message text,
  relay_host text,
  alias_addr text,
  www text,
  host_php text,
  host_cgi text,
  host_ssl text,
  host_dir text,
  soa text,
  ttl integer,
  mail text,
  logrotate text,
  log_rotate_num integer,
  log_mail_addr text,
  log_when_rotate text,
  log_rotate_size integer,
  log_compress text,
  log_rotate_size_ext text,
  suid integer,
  webmail text,
  webstats_url text
);