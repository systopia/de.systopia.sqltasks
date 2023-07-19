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

  $taskOptions = CRM_Sqltasks_Task::getExecutionTaskListOptions([
      'isShowDisabledTasks' => $isShowDisabledTasks
  ]);

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
}
