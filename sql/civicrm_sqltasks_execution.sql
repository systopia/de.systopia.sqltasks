CREATE TABLE IF NOT EXISTS `civicrm_sqltasks_execution` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique SqltasksExecution ID',
  `sqltask_id` int unsigned COMMENT 'FK to SQL Task',
  `start_date` datetime NOT NULL COMMENT 'Start date of execution',
  `end_date` datetime NULL COMMENT 'End date of execution',
  `runtime` int unsigned NULL COMMENT 'Task runtime in milliseconds',
  `input` longtext NULL COMMENT 'Task input',
  `log` longtext NULL COMMENT 'Task result log',
  `files` longtext NULL COMMENT 'Task result files (JSON)',
  `error_count` int unsigned NULL COMMENT 'Task execution error count',
  `created_id` int unsigned NULL COMMENT 'Contact ID of task executor',
  CONSTRAINT FK_civicrm_sqltasks_execution_sqltask_id FOREIGN KEY (`sqltask_id`) REFERENCES `civicrm_sqltasks`(`id`) ON DELETE SET NULL,
  CONSTRAINT FK_civicrm_sqltasks_execution_created_id FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL,
  PRIMARY KEY (`id`)
)
ENGINE=InnoDB;
