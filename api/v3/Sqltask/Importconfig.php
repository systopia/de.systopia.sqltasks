<?php

/**
 * Import sql task config
 *
 * @param $params
 *
 * @return array
 * @throws \Exception
 */
function civicrm_api3_sqltask_importconfig($params) {
  $task = CRM_Sqltasks_Task::getTask($params['id']);
  if (empty($task)) {
    return civicrm_api3_create_error('Task(id=' . $params['id'] . ') does not exist.');
  }

  if ($task->isArchived()) {
    return civicrm_api3_create_error('Task(id=' . $params['id'] . ') is archived. Can not import config.');
  }

  $data = NULL;
  if (!empty($params['import_json_data']) && is_array($params['import_json_data'])) {
    $data = CRM_Sqltasks_Config_Format::toLatest($params['import_json_data']);
  }
  elseif (!empty($params['import_data'])) {
    $data = CRM_Sqltasks_Config_Format::toLatest($params['import_data']);
  }
  else {
    return civicrm_api3_create_error(ts('Can\'t parse config file.'));
  }
  foreach ($data as $key => $value) {
    if ($key == 'config') {
      $task->setConfiguration($value);
    } else {
      $task->setAttribute($key, $value);
    }
  }
  $task->store();

  return civicrm_api3_create_success($task->getPreparedTask());
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_importconfig_spec(&$params) {
  $params['id'] = [
    'name'         => 'id',
    'api.required' => 1,
    'api.aliases'  => ['task_id'],
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Task ID',
    'description'  => 'Task ID',
  ];

  $params['import_json_data'] = [
    'name'         => 'import_json_data',
    'api.required' => 1,
    'api.aliases'  => ['import_json_data'],
    'type'         => CRM_Utils_Type::T_TEXT,
    'title'        => 'Import json data',
    'description'  => 'Import json data',
  ];
}
