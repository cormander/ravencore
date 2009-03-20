CREATE TABLE autoresponder_data (
	active integer not null,
	message varchar,
	subject varchar,
	charset varchar,
	forward varchar,
	address varchar,
	local_domains varchar
);
