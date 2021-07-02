-- CREATE civicrm_sqltasks_template TABLE
CREATE TABLE IF NOT EXISTS `civicrm_sqltasks_template`(
  `id`              int unsigned NOT NULL AUTO_INCREMENT,
  `name`            varchar(255) COMMENT 'name of the template',
  `description`     text         COMMENT 'template description',
  `config`          text         COMMENT 'configuration (JSON)',
  `last_modified`   datetime     COMMENT 'last time the template has been modified',
  PRIMARY KEY ( `id` )
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
