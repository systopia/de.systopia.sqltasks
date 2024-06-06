<?php

/**
 * Sets new order of all tasks
 * Sqltask.sort API
 *
 * @param $params
 *
 * @return array API result descriptor
 */
function civicrm_api3_sqltask_sort($params) {
  $before_order = $params['before_sort_tasks_order'];
  $after_order = $params['after_sort_tasks_order'];

  if ($before_order != CRM_Sqltasks_BAO_SqlTask::getTaskOrder()) {
    return civicrm_api3_create_error(
      'Task order can\'t be modified. ' .
      'Task order in database must be equal to entered task order.'
    );
  }

  CRM_Sqltasks_BAO_SqlTask::updateTaskOrder($after_order);

  return civicrm_api3_create_success(['Task order has successfully been modified.']);
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_sort_spec(&$params) {
  $params['after_sort_tasks_order'] = [
    'name'         => 'after_sort_tasks_order',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Task order after sort',
    'description'  => 'It is list of tasks ids. That order will be saved to database.',
  ];

  $params['before_sort_tasks_order'] = [
    'name'         => 'before_sort_tasks_order',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Task order before sort',
    'description'  => 'It is list of tasks ids. That order will be compared with order from database.',
  ];
}
