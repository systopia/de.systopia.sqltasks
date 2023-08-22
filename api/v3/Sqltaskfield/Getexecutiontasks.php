<?php

/**
 * Gets list of execution tasks(prepared for select)
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltaskfield_getexecutiontasks($params) {

  $isShowDisabledTasks = 0;
  if (!empty($params['is_show_disabled_tasks']) && $params['is_show_disabled_tasks'] == 1) {
    $isShowDisabledTasks = 1;
  }

  $searchParams = [
    'isShowDisabledTasks' => $isShowDisabledTasks
  ];

  if (!empty($params['excluded_task_id'])) {
    $searchParams['excludedTaskId'] = (int) $params['excluded_task_id'];
  }

  $taskOptions = CRM_Sqltasks_Task::getExecutionTaskListOptions($searchParams);

  return civicrm_api3_create_success([$taskOptions]);
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltaskfield_getexecutiontasks_spec(&$params) {
  $params['is_show_disabled_tasks'] = [
    'name'         => 'is_show_disabled_tasks',
    'api.required' => 0,
    'api.aliases'  => ['current_task_id'],
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Current task ID',
    'description'  => 'Current task ID',
  ];
  $params['excluded_task_id'] = [
    'name'         => 'excluded_task_id',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Excluded_task_id',
    'description'  => 'Removes this tasks from result',
  ];
  $params['is_show_disabled_tasks'] = [
    'name'         => 'is_show_disabled_tasks',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_BOOLEAN,
    'title'        => 'Is show disabled tasks?',
  ];
}
