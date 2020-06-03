-- CREATE civicrm_sqltasks TABLE
CREATE TABLE IF NOT EXISTS `civicrm_sqltasks`(
  `id`              int unsigned NOT NULL AUTO_INCREMENT,
  `name`            varchar(255) COMMENT 'name of the task',
  `description`     text         COMMENT 'task description',
  `category`        varchar(64)  COMMENT 'task category',
  `scheduled`       varchar(256) COMMENT 'scheduling information',
  `enabled`         int unsigned COMMENT 'is task enabled',
  `weight`          int unsigned COMMENT 'defines execution order',
  `last_execution`  datetime     COMMENT 'last time this task was executed',
  `running_since`   datetime     COMMENT 'set while task is being executed',
  `run_permissions` varchar(256) COMMENT 'permissions required to run',
  `input_required`  tinyint NOT NULL DEFAULT 0 COMMENT 'should have a mandatory form field?',
  `archive_date`    datetime NULL DEFAULT NULL COMMENT 'archive date',
  `last_runtime`    int unsigned COMMENT 'stores the runtime of the last execution in milliseconds',
  `parallel_exec`   tinyint NOT NULL DEFAULT 0 COMMENT 'should this task be executed in parallel?',
  `main_sql`        text         COMMENT 'main script (SQL)',
  `post_sql`        text         COMMENT 'cleanup script (SQL)',
  `config`          text         COMMENT 'configuration (JSON)',
  PRIMARY KEY ( `id` )
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
