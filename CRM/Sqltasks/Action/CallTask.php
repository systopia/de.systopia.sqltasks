<?php
/*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2018 SYSTOPIA                            |
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

use Civi\Api4;
use CRM_Sqltasks_ExtensionUtil as E;

/**
 * This action will "simply" call another task
 *
 */
class CRM_Sqltasks_Action_CallTask extends CRM_Sqltasks_Action {

  /**
   * Get identifier string
   */
  public function getID() {
    return 'task';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('Run SQL Task(s)');
  }

  /**
   * Get default template order
   *
   * @return int
   */
  public function getDefaultOrder() {
    return 800;
  }

  /**
   * RUN this action
   */
  public function execute() {
    $this->resetHasExecuted();

    $task_ids = $this->getConfigValue('tasks') ?? [];
    $categories = $this->getConfigValue('categories') ?? [];
    $execute_disabled_tasks = $this->getConfigValue('is_execute_disabled_tasks') == 1;

    $execute_in_parallel =
      $this->getConfigValue('execute_in_parallel') == 1
      && self::backgroundQueueEnabled();

    if (empty($task_ids) && empty($categories)) return;

    $tasks_2_run = self::findTasks([
      'categories'             => $categories,
      'execute_disabled_tasks' => $execute_disabled_tasks,
      'task_ids'               => $task_ids,
    ]);

    $queued_execs = [];
    $error_count = 0;

    foreach ($tasks_2_run as $task) {
      $task_id = $task->getID();
      $task_name = $task->getAttribute('name');

      if ($task_id == $this->task->getID()) continue;

      if ($execute_in_parallel) {
        $queued_execs[] = $task->executeAsync()['execution_id'];
        $this->log("Queued task '$task_name' [$task_id] for execution");
        continue;
      }

      $exec_result = $task->execute();

      foreach ($exec_result['logs'] as $log) {
        $this->log($log);
      }

      $this->log("Executed task '$task_name' [$task_id]");
    }

    while (!empty($queued_execs)) {
      $queued_execs_copy = $queued_execs;

      foreach ($queued_execs_copy as $i => $exec_id) {
        if (self::executionHasEnded($exec_id)) {
          unset($queued_execs[$i]);
          $error_count += self::getErrorCount($exec_id);
        }
      }

      sleep(1);
    }

    if ($error_count > 0) {
      throw new Exception("Execution of task $task_id encountered errors.");
    }
  }

  /**
   * Check whether task execution in background queues is enabled
   *
   * @return bool
   */
  private static function backgroundQueueEnabled() {
    $setting = Api4\Setting::get()
      ->addSelect('enableBackgroundQueue')
      ->execute()
      ->first();

    return $setting['value'] == '1';
  }

  /**
   * Check whether a task execution has ended
   *
   * @param int $execution_id
   * @return bool
   */
  private static function executionHasEnded($execution_id) {
    $end_date = CRM_Core_DAO::singleValueQuery(
      'SELECT end_date FROM civicrm_sqltasks_execution WHERE id = %1',
      [ 1 => [$execution_id, 'Integer'] ]
    );

    return !is_null($end_date);
  }

  /**
   * Select SQL Tasks by ID/category
   *
   * @param array $params
   * @return CRM_Sqltasks_Task[]
   */
  private static function findTasks($params) {
    $archived_clause = "archive_date IS NULL";
    $enabled_clause = $params['execute_disabled_tasks'] ? '1' : 'enabled = 1';

    $id_list = implode(',', $params['task_ids']);
    $id_clause = empty($params['task_ids']) ? '0' : "id IN ($id_list)";

    $category_list = implode(',',
      array_map(fn($c) => "'" . CRM_Core_DAO::escapeString($c) . "'", $params['categories'])
    );

    $category_clause = empty($params['categories']) ? '0' : "category IN ($category_list)";

    $query = "
      SELECT * FROM `civicrm_sqltasks`
      WHERE
        $archived_clause
        AND $enabled_clause
        AND (
          $id_clause
          OR $category_clause
        )
      ORDER BY weight ASC
    ";

    return CRM_Sqltasks_Task::getTasks($query);
  }

  /**
   * Get error count of a task execution
   *
   * @param int $execution_id
   * @return int
   */
  private static function getErrorCount($execution_id) {
    $error_count = CRM_Core_DAO::singleValueQuery(
      'SELECT error_count FROM civicrm_sqltasks_execution WHERE id = %1',
      [ 1 => [$execution_id, 'Integer'] ]
    );

    return (int) $error_count;
  }

  /**
   * Get a list of all SQL Tasks
   */
  protected function getTaskList() {
    $task_options = array();
    $task_list = CRM_Sqltasks_Task::getExecutionTaskList();

    // make sure this one is not in it
    foreach ($task_list as $task) {
      $task_id = $task->getID();
      if ($task_id != $this->task->getID()) {
        $task_options[$task_id] = "[{$task_id}] " . $task->getAttribute('name');
      }
    }

    return $task_options;
  }

  /**
   * Get a list of all SQL Task categories
   */
  protected function getTaskCategoryList() {
    $categoryOptions = [];
    $categories = CRM_Sqltasks_Task::getTaskCategoryList();

    foreach ($categories as $category) {
      $categoryOptions[$category] = $category;
    }

    return $categoryOptions;
  }
}
