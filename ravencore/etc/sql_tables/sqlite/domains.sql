DROP TABLE IF EXISTS domains;
CREATE TABLE domains (
  id int(10),
  created varchar(10),
  name varchar(80),
  subdomain int(10),
  uid int(10),
  host_type varchar(255),
  hosting varchar(5),
  redirect_url varchar(255),
  catchall varchar(20),
  catchall_addr varchar(120),
  bounce_message varchar(255),
  relay_host varchar(120),
  alias_addr varchar(120),
  www varchar(5),
  host_php varchar(5),
  host_cgi varchar(5),
  host_ssl varchar(5),
  host_dir varchar(5),
  soa varchar(255),
  ttl int(10),
  mail varchar(5),
  logrotate varchar(5),
  log_rotate_num int(10),
  log_mail_addr varchar(120),
  log_when_rotate varchar(10),
  log_rotate_size int(10),
  log_compress varchar(5),
  log_rotate_size_ext varchar(5),
  suid int(10),
  webmail varchar(5),
  webstats_url varchar(5)
);
