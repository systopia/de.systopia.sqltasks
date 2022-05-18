<?php

/**
 * Get tasks list
 *
 * @return array
 */
function civicrm_api3_sqltask_getalltasks() {
  $preparedTasks = [];
  $tasks = CRM_Sqltasks_Task::getAllTasks();

  foreach ($tasks as $task) {
    $preparedTasks[] = $task->getPreparedTask();
  }

  return civicrm_api3_create_success($preparedTasks);
}
