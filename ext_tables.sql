#
# Table structure for table 'tx_securedownloads_domain_model_log'
#
CREATE TABLE tx_securedownloads_domain_model_log (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	file_id varchar(255) DEFAULT NULL,
	file_name varchar(255) DEFAULT '' NOT NULL,
	file_path varchar(255) DEFAULT '' NOT NULL,
	file_size int(11) DEFAULT '0' NOT NULL,
	file_type varchar(255) DEFAULT '' NOT NULL,
	media_type varchar(255) DEFAULT '' NOT NULL,
	bytes_downloaded int(11) DEFAULT '0' NOT NULL,
	protected varchar(255) DEFAULT '' NOT NULL,
	host varchar(255) DEFAULT '' NOT NULL,
	typo3_mode varchar(255) DEFAULT '' NOT NULL,
	user int(11) unsigned DEFAULT '0',
	page int(11) DEFAULT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(255) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,

	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumblob,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
 KEY language (l10n_parent,sys_language_uid)

);