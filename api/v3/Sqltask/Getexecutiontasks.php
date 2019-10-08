<?php

/**
 * Gets list of execution tasks(prepared for select)
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltask_getexecutiontasks($params) {
  $taskOptions = [];
  $tasks = CRM_Sqltasks_Task::getExecutionTaskList();

  foreach ($tasks as $task) {
    $taskOptions[$task->getID()] = "[{$task->getID()}] " . $task->getAttribute('name');
  }

  if (!empty($params['current_task_id']) && isset($taskOptions[$params['current_task_id']])) {
    unset($taskOptions[$params['current_task_id']]);
  }

  return civicrm_api3_create_success([$taskOptions]);
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_getexecutiontasks_spec(&$params) {
  $params['current_task_id'] = [
    'name'         => 'current_task_id',
    'api.required' => 0,
    'api.aliases'  => ['current_task_id'],
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Current task ID',
    'description'  => 'Current task ID',
  ];
}
