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

/**
 * SQL Task Execution
 */
function civicrm_api3_sqltask_execute($params) {

  // If task_id given run only this one task
  if (!empty($params['task_id'])) {
    $task = CRM_Sqltasks_Task::getTask($params['task_id']);
    $result = $task->execute();
    return civicrm_api3_create_success($result);
  }

  // DEFAULT MODE:
  //   run all enabled tasks according to schedule
  //
  $tasks = CRM_Sqltasks_Task::getExecutionTaskList();
  $results = array();
  foreach ($tasks as $task) {
    if ($tasks->isScheduled()) {
      $results[] = $task->execute();
    }
  }

  return civicrm_api3_create_success($results);
}

/**
 * SQL Task Execution
 */
function _civicrm_api3_sqltask_execute_spec(&$params) {
  $params['task_id'] = array(
    'name'         => 'task_id',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Task ID',
    'description'  => 'If given, only this task will run. Regardless of scheduling and time',
    );
}
