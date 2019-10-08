<?php

/**
 * SQL Task Execution
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltask_execute($params) {

  $exec_params = [
    'log_to_file' => $params['log_to_file'],
    'input_val' => $params['input_val'],
  ];
  // If task_id given run only this one task
  if (!empty($params['id'])) {
    $task = CRM_Sqltasks_Task::getTask($params['id']);
    if (empty($task)) {
      return civicrm_api3_create_error('Task(id=' . $params['id'] . ') does not exist.');
    }

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
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_execute_spec(&$params) {
  $params['id'] = array(
    'name'         => 'id',
    'api.required' => 0,
    'api.aliases'  => ['task_id'],
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

