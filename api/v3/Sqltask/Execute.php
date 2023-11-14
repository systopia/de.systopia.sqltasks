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
    'execution_id' => $params['execution_id'] ?? NULL,
    'input_val'    => $params['input_val'] ?? NULL,
    'log_to_file'  => $params['log_to_file'] ?? 0,
  ];

  // If task_id given run only this one task
  if (!empty($params['id'])) {
    $task_id = $params['id'];
    $task = CRM_Sqltasks_Task::getTask($task_id);

    if (empty($task)) {
      return civicrm_api3_create_error("Task(id=$task_id) does not exist.");
    }

    if ($task->isArchived()) {
      return civicrm_api3_create_error("Task(id=$task_id) is archived. Can not execute Task.");
    }

    if ($task->inputRequired() && empty($params['input_val'])) {
      return civicrm_api3_create_error('Input value is required.');
    }

    if (!empty($params['check_permissions']) && !$task->allowedToRun()) {
      return civicrm_api3_create_error("Insufficient permissions to run task [$task_id].");
    }

    if (!empty($params['async'])) {
      $exec_result = $task->executeAsync($exec_params);
    } else {
      $exec_result = $task->execute($exec_params);
    }

    return civicrm_api3_create_success($exec_result);
  }

  // Run all enabled tasks according to schedule
  $results = CRM_Sqltasks_Task::runDispatcher($exec_params);
  $tasks = $results['tasks'];

  if (!empty($params['log_to_file'])) {
    // Don't return logs if we're logging to file, return count instead
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
  $params['async'] = array(
    'name'         => 'async',
    'api.required' => FALSE,
    'api.default'  => FALSE,
    'type'         => CRM_Utils_Type::T_BOOLEAN,
    'title'        => 'Async (background execution)',
    'description'  => 'Execute the task in a background queue?',
  );
  $params['execution_id'] = array(
    'name'         => 'execution_id',
    'api.required' => FALSE,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Execution ID',
    'description'  => 'ID of an existing SQLTask Execution',
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
