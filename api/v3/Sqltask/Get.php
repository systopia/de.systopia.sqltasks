<?php

/**
 * Get task
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltask_get($params) {
  $task = CRM_Sqltasks_Task::getTask($params['id']);
  if (empty($task)) {
    return civicrm_api3_create_error('Task(id=' . $params['id'] . ') does not exist.');
  }

  return civicrm_api3_create_success($task->getPreparedTask());
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_get_spec(&$params) {
  $params['id'] = [
    'name' => 'id',
    'api.required' => 1,
    'api.aliases' => ['task_id'],
    'type' => CRM_Utils_Type::T_INT,
    'title' => 'Task ID',
    'description' => 'Unique task ID',
  ];
}
