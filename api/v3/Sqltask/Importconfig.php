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
  $task_id = $params['id'];
  $task = CRM_Sqltasks_BAO_SqlTask::findById($task_id);

  if (empty($task)) {
    return civicrm_api3_create_error("Task(id=$task_id) does not exist.");
  }

  if (!is_null($task->archive_date)) {
    return civicrm_api3_create_error("Task(id=$task_id) is archived. Can not import config.");
  }

  if (!empty($params['import_json_data']) && is_array($params['import_json_data'])) {
    $data = CRM_Sqltasks_Config_Format::toLatest($params['import_json_data']);
  } else if (!empty($params['import_data'])) {
    $data = CRM_Sqltasks_Config_Format::toLatest($params['import_data']);
  } else {
    return civicrm_api3_create_error(ts("Can't parse config file."));
  }

  $task->updateAttributes($data);

  return civicrm_api3_create_success($task->exportData());
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
