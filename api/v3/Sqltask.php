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

  $exec_params = [
    'log_to_file' => $params['log_to_file'],
    'input_val' => $params['input_val'],
  ];
  // If task_id given run only this one task
  if (!empty($params['task_id'])) {
    $task = CRM_Sqltasks_Task::getTask($params['task_id']);
    if ($task->allowedToRun()) {
      $timestamp = microtime(TRUE);
      $result = $task->execute($exec_params);
      return civicrm_api3_create_success([
          "log"     => $result,
          "files"   => CRM_Sqltasks_Task::getAllFiles(),
          'runtime' => microtime(TRUE) - $timestamp,
      ]);
    } else {
      return civicrm_api3_create_error("Insufficient permissions to run task [{$params['task_id']}].");
    }
  }

  // DEFAULT MODE:
  //   run all enabled tasks according to schedule
  $results = CRM_Sqltasks_Task::runDispatcher($exec_params);
  if (!empty($params['log_to_file'])) {
    // don't return logs if we're logging to file, return count instead
    $results = count($results);
  }
  return civicrm_api3_create_success($results);
}

/**
 * SQL Task Execution
 */
function _civicrm_api3_sqltask_execute_spec(&$params) {
  $params['task_id'] = array(
    'name'         => 'task_id',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Task ID',
    'description'  => 'If given, only this task will run. Regardless of scheduling and time',
  );
  $params['log_to_file'] = array(
    'name'         => 'log_to_file',
    'api.required' => 0,
    'api.default'  => 0,
    'type'         => CRM_Utils_Type::T_BOOLEAN,
    'title'        => 'Log to a file?',
    'description'  => 'Log task output to a file instead of returning it in the API results?',
  );
}

/**
 * Sqltask.sort API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_sort_spec(&$params) {

  $params['data'] = array(
    'name'         => 'data',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'New taskorder/weight data ',
    'description'  => 'New taskorder for resorting and saving to database.',
  );

  $params['task_screen_order'] = array(
    'name'         => 'task_screen_order',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Old taskorder/weight data ',
    'description'  => 'Screen taskorder for comparing with new taskorder.',
  );
}

/**
 * Sqltask.sort API
 *
 * Updates the order of the SQL Tasks
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_sqltask_sort($params) {

  try {
    $tasksorderNew = $params['data'];
    $taskScreenOrder = $params['task_screen_order'];

    // create new taskorder array for comparison with old taskorder array
    foreach ($taskScreenOrder as $key => $task) {
      $task = explode('_', $task);
      $taskScreenOrder[$key] = $task[0];
    }

    // fetch the task sorting from database
    $query = "SELECT id FROM civicrm_sqltasks ORDER BY weight ASC, id ASC";
    $result = CRM_Core_DAO::executeQuery($query);
    $tasksorderDatabase = [];

    while ($result->fetch()) {
      $tasksorderDatabase[] = $result->id;
    }

    // create new taskorder array for comparison with old taskorder array
    foreach ($tasksorderNew as $key => $task) {
      $task = explode('_', $task);
      $tasksorderNew[$key] = $task[0];
    }

    // check the difference between taskorder array from database and the taskorder array from the screen
    if ($taskScreenOrder != $tasksorderDatabase) {
      return civicrm_api3_create_error('Task order was modified');
    }

    foreach ($tasksorderNew as $key => $task) {
      $weight = ($key * 10) + 10;
      $query = "UPDATE civicrm_sqltasks SET weight = %1 WHERE id = %2";
      $sqlParams = [
        1 => [$weight, 'String'],
        2 => [$task, 'Integer'],
      ];

      CRM_Core_DAO::executeQuery($query, $sqlParams);
    }

    return civicrm_api3_create_success(array(TRUE));
  }
  catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}
