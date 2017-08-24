-- CREATE
CREATE TABLE IF NOT EXISTS `civicrm_sqltasks`(
  `id`             int unsigned NOT NULL AUTO_INCREMENT,
  `name`           varchar(64)  COMMENT 'name of the task',
  `description`    varchar(256) COMMENT 'task description',
  `scheduled`      varchar(256) COMMENT 'scheduling information',
  `enabled`        int unsigned COMMENT 'is task enabled',
  `weight`         int unsigned COMMENT 'defines execution order',
  `last_execution` datetime     COMMENT 'last time this task was executed',
  `pre_sql`        text         COMMENT 'SQL to be executed first',
  `select_sql`     text         COMMENT 'SQL to be executed second, yielding the selection',
  `post_sql`       text         COMMENT 'SQL to be executed last, usually cleanup',
  `config`         text         COMMENT 'configuration (JSON)',
  PRIMARY KEY ( `id` )
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
