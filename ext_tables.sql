#
# Table structure for table 'tx_nawsecuredl_counter'
#
CREATE TABLE tx_nawsecuredl_counter (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    file_id tinytext NOT NULL,
    file_path tinytext NOT NULL,
    file_type varchar(4) DEFAULT '' NOT NULL,
    media_type tinyint(4) unsigned DEFAULT '0' NOT NULL,
    file_name text NOT NULL,
    file_size bigint(32) unsigned DEFAULT '0' NOT NULL,
    bytes_downloaded int(11) unsigned DEFAULT '0' NOT NULL,
    protected varchar(30) DEFAULT '' NOT NULL,
    host varchar(30) DEFAULT '' NOT NULL,
    user_id int(11) DEFAULT '0' NOT NULL,
    user_group int(11) DEFAULT '0' NOT NULL,
    page_id int(11) DEFAULT '0' NOT NULL,
    app_id varchar(30) DEFAULT '' NOT NULL,
    sitetitle varchar(30) DEFAULT '' NOT NULL,
    typo3_mode char(2) DEFAULT '' NOT NULL,


    PRIMARY KEY (uid),
    KEY parent (pid)
);