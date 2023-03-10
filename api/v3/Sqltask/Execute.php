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
    'log_to_file' => $params['log_to_file'] ?? 0,
    'input_val' => $params['input_val'],
  ];
  // If task_id given run only this one task
  if (!empty($params['id'])) {
    $task = CRM_Sqltasks_Task::getTask($params['id']);
    if (empty($task)) {
      return civicrm_api3_create_error('Task(id=' . $params['id'] . ') does not exist.');
    }

    if ($task->isArchived()) {
      return civicrm_api3_create_error('Task(id=' . $params['id'] . ') is archived. Can not execute Task.');
    }

    if (empty($params['input_val']) && $task->getAttribute('input_required') == 1) {
      return civicrm_api3_create_error('Input value is required.');
    }

    if (empty($params['check_permissions']) || $task->allowedToRun()) {
      $timestamp = microtime(TRUE);
      $taskExecutionResult = $task->execute($exec_params);
      $success_data = [
        "log"     => $taskExecutionResult['logs'],
        "files"   => CRM_Sqltasks_Task::getAllFiles(),
        'runtime' => microtime(TRUE) - $timestamp,
      ];
      if (!empty($task->getReturnValues())) {
        foreach ($task->getReturnValues() as $key => $value) {
          $success_data[$key] = $value;
        }
      }

      return civicrm_api3_create_success($success_data);
    } else {
      return civicrm_api3_create_error("Insufficient permissions to run task [{$params['task_id']}].");
    }
  }

  // DEFAULT MODE:
  //   run all enabled tasks according to schedule
  $results = CRM_Sqltasks_Task::runDispatcher($exec_params);
  $tasks = $results['tasks'];
  if (!empty($params['log_to_file'])) {
    // don't return logs if we're logging to file, return count instead
    $tasks = count($tasks);
  }
  $dao = NULL;
  return civicrm_api3_create_success($tasks, [], NULL, NULL, $dao, [
    'summary' => $results['summary'],
  ]);
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
  $params['input_val'] = array(
    'name'         => 'input_val',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Input Value',
    'description'  => 'Input value with execution context. Will be forwarded to all actions',
  );
}
