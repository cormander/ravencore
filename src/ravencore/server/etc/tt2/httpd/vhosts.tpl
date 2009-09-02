<Directory [% vhost_root %]>
	Order allow,deny
	Allow from all
</Directory>

[% FOREACH ip_addr = ip_addresses %][% IF ip_addr.ports.80.0 %]NameVirtualHost [% ip_addr.ip_addr %]:80
[% END %][% IF ip_addr.ports.443.0 %]NameVirtualHost [% ip_addr.ip_addr %]:443
[% END %][% IF ip_addr.ports.443.0 %]NameVirtualHost [% ip_addr.ip_addr %]:443
[% END %][% END %]
[% FOREACH ip_addr = ip_addresses %][% FOREACH domain = ip_addr.ports.80 %][% INCLUDE vhost_domain.tpl port = 80 %][% END %][% END %]
[% FOREACH ip_addr = ip_addresses %][% FOREACH domain = ip_addr.ports.443 %][% INCLUDE vhost_domain.tpl port = 443 %][% END %][% END %]
