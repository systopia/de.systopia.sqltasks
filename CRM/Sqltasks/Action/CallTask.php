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
  public static function getDefaultOrder() {
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
      $task_id = $task['id'];
      $task_name = $task['name'];

      if ($task_id == $this->task->id) continue;

      $task = CRM_Sqltasks_BAO_SqlTask::findById($task_id);

      if ($execute_in_parallel) {
        $queued_execs[] = $task->enqueue()['execution_id'];
        $this->log("Queued task '$task_name' [$task_id] for execution");
        continue;
      }

      $exec_result = $task->execute();

      foreach ($exec_result['logs'] as $log) {
        $this->log($log);
      }

      $this->log("Executed task '$task_name' [$task_id]");
    }

    if (!empty($queued_execs)) {
      $this->log('Waiting for queued tasks to complete');
    }

    while (!empty($queued_execs)) {
      $queued_execs_copy = $queued_execs;

      foreach ($queued_execs_copy as $i => $exec_id) {
        if (!self::executionHasEnded($exec_id)) continue;

        unset($queued_execs[$i]);
        $execution = CRM_Sqltasks_BAO_SqltasksExecution::findById($exec_id);
        $exec_logs = CRM_Sqltasks_BAO_SqltasksExecution::prepareLogs($execution->log);

        foreach ($exec_logs as $log_entry) {
          $this->log($execution->renderLogMessage($log_entry));
        }

        if ((int) $execution->error_count < 1) continue;

        throw new Exception("Execution of task {$execution->sqltask_id} encountered errors.");
      }
      // sleep 0.2s
      usleep(200000);
    }
  }

  /**
   * Check whether task execution in background queues is enabled
   *
   * @return bool
   */
  private static function backgroundQueueEnabled() {
    $setting = Api4\Setting::get(FALSE)
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
   * @return array
   */
  private static function findTasks($params) {
    $query = Api4\SqlTask::get(FALSE)->addSelect('id', 'name');

    $query->addWhere('archive_date', 'IS NULL');

    if (!$params['execute_disabled_tasks']) {
      $query->addWhere('enabled', '=', 1);
    }

    $random_value = bin2hex(random_bytes(16));
    $task_ids = empty($params['task_ids']) ? [$random_value] : $params['task_ids'];
    $categories = empty($params['categories']) ? [$random_value] : $params['categories'];

    $query->addClause('OR',
      ['id',       'IN', $task_ids],
      ['category', 'IN', $categories]
    );

    $query->addOrderBy('weight', 'ASC');

    return $query->execute()->getArrayCopy();
  }

}
