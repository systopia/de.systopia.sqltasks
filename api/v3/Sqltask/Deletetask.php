<?php

/**
 * Delete task
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltask_deletetask($params) {
  CRM_Sqltasks_Task::delete($params['id']);

  return civicrm_api3_create_success(['Task doesn\'t exist anymore.']);
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_deletetask_spec(&$params) {
  $params['id'] = [
    'name'         => 'id',
    'api.required' => 1,
    'api.aliases'  => ['task_id'],
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Task ID',
    'description'  => 'Unique task ID',
  ];
}
