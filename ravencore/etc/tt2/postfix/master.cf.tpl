[% IF content_filter ]127.0.0.1:10025	inet n  -       n     -       -  smtpd -o content_filter= -o cleanup_service_name=amavisd-cleanup
amavisd-cleanup	unix   n   -   n   -   0   cleanup -o virtual_alias_maps=[% END %]
anvil	unix  -       -       n       -       1       anvil
bounce	unix  -       -       n       -       0       bounce
bsmtp	unix  -       n       n       -       -       pipe flags=Fq. user=foo argv=/usr/local/sbin/bsmtp -f $sender $nexthop $recipient
cleanup	unix  n       -       n       -       0       cleanup
cyrus	unix  -       n       n       -       -       pipe user=cyrus argv=/usr/lib/cyrus-imapd/deliver -e -r ${sender} -m ${extension} ${user}
defer	unix  -       -       n       -       0       bounce
error	unix  -       -       n       -       -       error
flush	unix  n       -       n       1000?   0       flush
ifmail	unix  -       n       n       -       -       pipe flags=F user=ftn argv=/usr/lib/ifmail/ifmail -r $nexthop ($recipient)
lmtp	unix  -       -       n       -       -       lmtp
local	unix  -       n       n       -       -       local
maildrop	unix  -       n       n       -       -       pipe flags=DRhu user=vmail argv=/usr/local/bin/maildrop -d ${recipient}
old-cyrus	unix  -       n       n       -       -       pipe flags=R user=cyrus argv=/usr/lib/cyrus-imapd/deliver -e -m ${extension} ${user}
pickup	fifo  n       -       n       60      1       pickup
proxymap	unix  -       -       n       -       -       proxymap
qmgr	fifo  n       -       n       300     1       qmgr
relay	unix  -       -       n       -       -       smtp
rewrite	unix  -       -       n       -       -       trivial-rewrite
showq	unix  n       -       n       -       -       showq
smtp	inet  n       -       n       -       -       smtpd
smtp	unix  -       -       n       -       -       smtp
submission	inet  n       -       n       -       -       smtpd
trace	unix  -       -       n       -       0       bounce
uucp	unix  -       n       n       -       -       pipe flags=Fqhu user=uucp argv=uux -r -n -z -a$sender - $nexthop!rmail ($recipient)
verify	unix  -       -       n       -       1       verify
virtual	unix  -       n       n       -       -       virtual
yaa	unix  -       n       n       -       -       pipe user=vmail argv=[% rc_root %]/var/apps/yaa/bin/yaa.pl
