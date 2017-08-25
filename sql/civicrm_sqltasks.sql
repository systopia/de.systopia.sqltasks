-- CREATE civicrm_sqltasks TABLE
CREATE TABLE IF NOT EXISTS `civicrm_sqltasks`(
  `id`             int unsigned NOT NULL AUTO_INCREMENT,
  `name`           varchar(64)  COMMENT 'name of the task',
  `description`    text         COMMENT 'task description',
  `scheduled`      varchar(256) COMMENT 'scheduling information',
  `enabled`        int unsigned COMMENT 'is task enabled',
  `weight`         int unsigned COMMENT 'defines execution order',
  `last_execution` datetime     COMMENT 'last time this task was executed',
  `main_sql`       text         COMMENT 'main script (SQL)',
  `post_sql`       text         COMMENT 'cleanup script (SQL)',
  `config`         text         COMMENT 'configuration (JSON)',
  PRIMARY KEY ( `id` )
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
