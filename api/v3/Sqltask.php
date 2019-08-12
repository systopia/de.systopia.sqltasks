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
    if ($task->allowedToRun()) {
      $timestamp = microtime(TRUE);
      $result = $task->execute();
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
  $results = CRM_Sqltasks_Task::runDispatcher();
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
}


/**
 * Sqltask.sort API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_sort_spec(&$params) {

  $params['data'] = array(
      'name'         => 'data',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'Task order/weight data ',
      'description'  => 'Resort tasks and save them to database.',
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

    $tasksOrder = $params['data'];

    foreach($tasksOrder as $key => $task) {

      $task = explode('_', $task);
      $weight = ($key*10) + 10;

      $query = "UPDATE civicrm_sqltasks SET weight = %1 WHERE id = %2";
      $sqlParams = array(
          1 => array($weight, 'String'),
          2 => array($task[0], 'Integer'));
      CRM_Core_DAO::executeQuery($query, $sqlParams);

    }

    return civicrm_api3_create_success(array(True));

  } catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}
