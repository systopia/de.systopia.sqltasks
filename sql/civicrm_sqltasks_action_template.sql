-- CREATE civicrm_sqltasks_action_template TABLE
CREATE TABLE `civicrm_sqltasks_action_template` (
    `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique SqltasksActionTemplates ID',
    `name` varchar(255) NOT NULL   COMMENT 'Action Template Name',
    `type` varchar(255) NOT NULL   COMMENT 'Action Template Type',
    `config` text NOT NULL   COMMENT 'Action Template Configuration'
  , PRIMARY KEY (`id`)
  , UNIQUE INDEX `index_unique_name_type`(name, type)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
