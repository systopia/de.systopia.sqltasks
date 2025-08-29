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
class CRM_Sqltasks_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Run install script
   */
  public function install() {
    $this->executeSqlFile('sql/civicrm_sqltasks.sql');
    $this->executeSqlFile('sql/civicrm_sqltasks_template.sql');
    $this->installDefaultTemplate();

    // update rebuild log tables
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
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

  /**
   * Update to version 0.8
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0081() {
    $this->ctx->log->info('Updating "SQL Tasks" adding run permissions...');

    // add column: last_runtime
    $column_exists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_sqltasks` LIKE 'run_permissions';");
    if (!$column_exists) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` ADD COLUMN `run_permissions` varchar(256) COMMENT 'permissions required to run';");
    }

    // update rebuild log tables
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();

    return TRUE;
  }

  /**
   * Update to version 0.7.5
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0082() {
    // input_required was added in 0.9, but we need it here to support direct
    // upgrades from < 0.8.2 to >= 0.9 as CRM_Sqltasks_Task::store() relies
    // on the column being present.
    $this->addInputRequired();
    $this->addArchiveDateColumn();
    $this->addAbortOnErrorColumn();
    $this->addLastModifiedColumn();

    foreach (CRM_Sqltasks_BAO_SqlTask::generator() as $task) {
      $scheduled = $task->scheduled;
      $scheduled_vars = [];

      switch ($scheduled) {
        case 'hourly':
          $scheduled_vars = ['', '', '', '', '0'];
          break;

        case 'daily':
          $scheduled_vars = ['', '', '', '0', '0'];
          break;

        case 'weekly':
          $scheduled_vars = ['', '1', '', '0', '0'];
          break;

        case 'monthly':
          $scheduled_vars = ['', '', '1', '0', '0'];
          break;

        case 'yearly':
          $scheduled_vars = ['1', '', '1', '0', '0'];
          break;

        case 'always':
        default:
          $scheduled_vars = ['', '', '', '', ''];
          break;
      }

      $scheduled_keys = [
        'scheduled_month',
        'scheduled_weekday',
        'scheduled_day',
        'scheduled_hour',
        'scheduled_minute',
      ];

      $config = json_decode($task->config, TRUE);

      foreach ($schedules_keys as $i => $key) {
        $config[$key] = CRM_Utils_Array::value($key, $config, $scheduled_vars[$i]);
      }

      $task->updateAttributes([ 'config' => $config ]);
    }

    return TRUE;
  }

  /**
   * Rebuild menu when done
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0083() {
    $this->ctx->log->info('Clear template cache and rebuild menu...');
    CRM_Core_Invoke::rebuildMenuAndCaches();
    return TRUE;
  }

  /**
   * Make sure CSV is an acceptable mime type
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0085() {
    $this->ctx->log->info("Making sure 'text/csv' and 'application/zip' are acceptable download mime types...");
    $mime_type_setting = Civi::settings()->get('requestableMimeTypes');
    $mime_type_list = explode(',', $mime_type_setting);
    $mime_type_list_changed = FALSE;
    if (!in_array('text/csv', $mime_type_list)) {
      $mime_type_list[] = 'text/csv';
      $mime_type_list_changed = TRUE;
    }
    if (!in_array('application/zip', $mime_type_list)) {
      $mime_type_list[] = 'application/zip';
      $mime_type_list_changed = TRUE;
    }
    if ($mime_type_list_changed) {
      Civi::settings()->set('requestableMimeTypes', implode(',', $mime_type_list));
    }

    return TRUE;
  }

  /**
   * Add input_required column if it doesn't exist
   */
  private function addInputRequired() {
    // add column: input_required
    $column_exists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_sqltasks` LIKE 'input_required';");
    if (!$column_exists) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` ADD COLUMN `input_required` tinyint COMMENT 'should this task require user input?';");
      // update rebuild log tables
      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferences();
    }
  }

  /**
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0090() {
    $this->ctx->log->info('Applying update');
    $this->addInputRequired();
    $table_exists = CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE 'civirule_action';");
    if ($table_exists) {
      CRM_Core_DAO::executeQuery(
        "INSERT INTO civirule_action (name, label, class_name, is_active)
            VALUES('run_sql_task', 'Run SQL Task', 'CRM_CivirulesActions_SQLTask', 1)"
      );
    }

    return TRUE;
  }

  /**
   * Upgrade task configuration format
   *
   * @return bool
   * @throws \Exception
   */
  public function upgrade_0100() {
    $this->addLastModifiedColumn();
    $this->ctx->log->info('Adding default values for parallel_exec, input_required');

    CRM_Core_DAO::executeQuery("
      UPDATE `civicrm_sqltasks`
      SET `parallel_exec` = 0
      WHERE `parallel_exec` IS NULL
    ");

    CRM_Core_DAO::executeQuery("
      ALTER TABLE `civicrm_sqltasks`
      CHANGE COLUMN `parallel_exec`
        `parallel_exec` TINYINT(4)
        NOT NULL
        DEFAULT 0
        COMMENT 'should this task be executed in parallel?'
    ");

    CRM_Core_DAO::executeQuery("
      UPDATE `civicrm_sqltasks`
      SET `input_required` = 0
      WHERE `input_required` IS NULL
    ");

    CRM_Core_DAO::executeQuery("
      ALTER TABLE `civicrm_sqltasks`
      CHANGE COLUMN `input_required`
        `input_required` TINYINT(4)
        NOT NULL
        DEFAULT 0
        COMMENT 'should have a mandatory form field?'
    ");

    $this->ctx->log->info('Upgrading task configuration to latest format');

    foreach (CRM_Sqltasks_BAO_SqlTask::generator() as $task) {
      $config = CRM_Sqltasks_Config_Format::toLatest(json_decode($task->config, TRUE))['config'];
      $task->updateAttributes([ 'config' => $config ]);
    }

    return TRUE;
  }

  /**
   * Update 'name' column in 'civicrm_sqltasks' table
   * Sets max length to 255
   *
   * @return bool
   * @throws \Exception
   */
  public function upgrade_0110() {
    $this->ctx->log->info('Change character limit(set 255) for \'name\' column in \'civicrm_sqltasks\' table.');
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` CHANGE COLUMN `name` `name` varchar(255) COMMENT 'name of the task'");

    // update rebuild log tables
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();

    return TRUE;
  }

  /**
   * Adds 'archive_date' column to 'civicrm_sqltasks' table if column doesn't exist
   */
  private function addArchiveDateColumn() {
    $this->ctx->log->info('Adding \'archive_date\' column to \'civicrm_sqltasks\' table if column doesn\'t exist');
    $isColumnExists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_sqltasks` LIKE 'archive_date';");
    if (!$isColumnExists) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` ADD COLUMN  `archive_date` datetime NULL DEFAULT NULL COMMENT 'archive date'");
      // update rebuild log tables
      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferences();
    }
  }

  /**
   * Add 'archive_date' column to 'civicrm_sqltasks' table if column doesn't exist
   *
   * @return bool
   * @throws \Exception
   */
  public function upgrade_0120() {
    $this->addArchiveDateColumn();

    return TRUE;
  }

  /**
   * Add column `abort_on_error` to table `civicrm_sqltasks`
   */
  public function addAbortOnErrorColumn () {
    $column_exists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_sqltasks` LIKE 'abort_on_error';");

    if (!$column_exists) {
      $this->ctx->log->info("Adding column `abort_on_error` tinyint NOT NULL DEFAULT 0 to table `civicrm_sqltasks`");
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` ADD `abort_on_error` tinyint NOT NULL DEFAULT 0 COMMENT 'should abort task execution on error?'");

      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferences();
    }
  }

  /**
   * @return bool
   * @throws \Exception
   */
  public function upgrade_0130 () {
    $this->addAbortOnErrorColumn();
    return true;
  }

  /**
   * Add column `last_modified` to table `civicrm_sqltasks`
   */
  public function addLastModifiedColumn () {
    $column_exists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_sqltasks` LIKE 'last_modified';");

    if (!$column_exists) {
      $this->ctx->log->info("Adding column `last_modified` tinyint NOT NULL DEFAULT 0 to table `civicrm_sqltasks`");
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` ADD `last_modified` datetime COMMENT 'last time the configuration of the task has been modified'");

      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferences();
    }
  }

  /**
   * @return bool
   * @throws \Exception
   */
  public function upgrade_0140 () {
    $this->addLastModifiedColumn();
    return true;
  }

  /**
   * Creates table `civicrm_sqltasks_template`
   */
  private function createTemplatesTable() {
    $tableName = "civicrm_sqltasks_template";
    $tableExists = CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '$tableName';");

    if (!$tableExists) {
      $this->ctx->log->info("Creating table `$tableName`");
      $this->executeSqlFile('sql/civicrm_sqltasks_template.sql');
      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferences();
    }
  }

  /**
   * Installs default template and sets it as default in settings
   */
  private function installDefaultTemplate() {
    $taskConfig = file_get_contents("tasks/default-template.sqltask", true);

    $sqltasksTemplate = CRM_Sqltasks_BAO_SqltasksTemplate::create([
        'name' => 'Default',
        'description' => 'Default template for new tasks',
        'config' => $taskConfig,
      ]
    );

    Civi::settings()->set('sqltasks_default_template', $sqltasksTemplate->id);
  }

  /**
   * @return bool
   * @throws \Exception
   */
  public function upgrade_0150 () {
    $this->createTemplatesTable();

    $this->ctx->log->info("Inserting default template into new table");
    $this->installDefaultTemplate();

    return true;
  }

  /**
   * Creates table `civicrm_sqltasks_action_template`
   */
  private function createActionTemplatesTable() {
    $tableName = "civicrm_sqltasks_action_template";
    $tableExists = CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '$tableName';");

    if (!$tableExists) {
      $this->ctx->log->info("Creating table `$tableName`");
      $this->executeSqlFile('sql/civicrm_sqltasks_action_template.sql');
      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferences();
    }
  }

  /**
   * @return bool
   * @throws \Exception
   */
  public function upgrade_0200() {
    $this->createActionTemplatesTable();
    return true;
  }

  /**
   * Remove columns main_sql and post_sql
   *
   * @return bool
   */
  public function upgrade_0201() {
    $column_exists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_sqltasks` LIKE 'main_sql';");

    if ($column_exists) {
      $this->ctx->log->info("Removing column `main_sql` and `post_sql`");
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sqltasks` DROP COLUMN `main_sql`, DROP COLUMN `post_sql`");

      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferences();
    }
    CRM_Core_Invoke::rebuildMenuAndCaches();
    return TRUE;
  }

  /**
   * @return bool
   * @throws \Exception
   */
  public function upgrade_0250() {
    $this->executeSqlFile('sql/civicrm_sqltasks_execution.sql');
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
    return true;
  }

    /**
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0300() {
        $this->ctx->log->info('Clear cache to activate new settings.');
        CRM_Core_Invoke::rebuildMenuAndCaches();
        return TRUE;
    }

    /**
     * Update logging triggers to apply log exclusions
     *
     * @return true
     */
    public function upgrade_0310() {
      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferences();
      return TRUE;
    }

    /**
     * Make column `civicrm_sqltasks_execution.start_date` nullable
     *
     * @return true
     */
    public function upgrade_0320() {
      $this->ctx->log->info("Make column `civicrm_sqltasks_execution.start_date` nullable");

      CRM_Core_DAO::executeQuery("
        ALTER TABLE `civicrm_sqltasks_execution`
        MODIFY `start_date` datetime COMMENT 'Start date of execution'
      ");

      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferences();
      return TRUE;
    }

}
