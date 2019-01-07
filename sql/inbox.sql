
CREATE TABLE /*_*/inbox_email (
	email_id int unsigned not null primary key auto_increment,
	email_headers BLOB null,
	email_to varchar(255) not null,
	email_from varchar(255) not null,
	email_subject varchar(2000) not null,
	email_body BLOB not null,
	email_timestamp binary(14) not null,
	email_read tinyint unsigned not null default 0
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/inbox_email_to ON /*_*/inbox_email (email_to);
