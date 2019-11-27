#
# Table structure for table 'tx_securedownloads_domain_model_log'
#
CREATE TABLE tx_securedownloads_domain_model_log (
	file_id varchar(255) DEFAULT NULL,
	file_name varchar(255) DEFAULT '' NOT NULL,
	file_path varchar(255) DEFAULT '' NOT NULL,
	file_size int(11) DEFAULT '0' NOT NULL,
	file_type varchar(255) DEFAULT '' NOT NULL,
	media_type varchar(255) DEFAULT '' NOT NULL,
	protected varchar(255) DEFAULT '' NOT NULL,
	host varchar(255) DEFAULT '' NOT NULL,
	user int(11) unsigned DEFAULT '0',
	page int(11) DEFAULT NULL
);
