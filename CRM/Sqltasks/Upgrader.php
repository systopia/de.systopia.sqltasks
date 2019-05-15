<?php
/*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Sqltasks_Upgrader extends CRM_Sqltasks_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Run install script
   */
  public function install() {
    $this->executeSqlFile('sql/civicrm_sqltasks.sql');

    // update rebuild log tables
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
  }

  /**
   * Update to version 0.7.5
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0075() {
    $tasks = CRM_Sqltasks_Task::getAllTasks();
    foreach ($tasks as $task) {
      $scheduled = $task->getAttribute('scheduled');
      $scheduled_vars = array();
      switch ($scheduled) {
        case 'hourly':
          $scheduled_vars = array('', '', '', '', '0');
          break;
        case 'daily':
          $scheduled_vars = array('', '', '', '0', '0');
          break;
        case 'weekly':
          $scheduled_vars = array('', '1', '', '0', '0');
          break;
        case 'monthly':
          $scheduled_vars = array('', '', '1', '0', '0');
          break;
        case 'yearly':
          $scheduled_vars = array('1', '', '1', '0', '0');
          break;
        case 'always':
        default:
          $scheduled_vars = array('', '', '', '', '');
          break;
      }
      list($scheduled_month, $scheduled_weekday, $scheduled_day, $scheduled_hour, $scheduled_minute) = $scheduled_vars;

      $config = $task->getConfiguration();
      $config['scheduled_month'] = $scheduled_month;
      $config['scheduled_weekday'] = $scheduled_weekday;
      $config['scheduled_day'] = $scheduled_day;
      $config['scheduled_hour'] = $scheduled_hour;
      $config['scheduled_minute'] = $scheduled_minute;
      $task->setConfiguration($config);
      $task->store();
    }

    return TRUE;
  }

  /**
   * Update to version 0.5
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0050() {
    $this->ctx->log->info('Updating "SQL Tasks" schema to version 0.5...');

    // add column: category
    $column_exists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_sqltasks` LIKE 'category';");
    if (!$column_exists) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` ADD COLUMN `category` varchar(64) COMMENT 'task category';");
    }

    // add column: running_since
    $column_exists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_sqltasks` LIKE 'running_since';");
    if (!$column_exists) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` ADD COLUMN `running_since` datetime COMMENT 'set while task is being executed';");
    }

    // add column: last_runtime
    $column_exists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_sqltasks` LIKE 'last_runtime';");
    if (!$column_exists) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` ADD COLUMN `last_runtime` int unsigned COMMENT 'stores the runtime of the last execution in milliseconds';");
    }

    // add column: parallel_exec
    $column_exists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_sqltasks` LIKE 'parallel_exec';");
    if (!$column_exists) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` ADD COLUMN `parallel_exec` tinyint COMMENT 'should this task be executed in parallel?';");
    }

    // update rebuild log tables
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();

    return TRUE;
  }
}
