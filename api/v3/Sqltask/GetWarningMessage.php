<?php

use Civi\Utils\Sqltasks\WarningMessages\WarningMessagesManager;

function civicrm_api3_sqltask_get_warning_message($params) {
  try {
    $result = WarningMessagesManager::getResult($params);
  } catch (Exception $e) {
    throw new CiviCRM_API3_Exception($e->getMessage());
  }

  return civicrm_api3_create_success($result);
}

function _civicrm_api3_sqltask_get_warning_message_spec(&$params) {
  $params['action'] = [
    'name' => 'action',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'Action',
    'description' => 'Action which user doing',
  ];
  $params['context'] = [
    'name' => 'context',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'Context',
    'description' => 'Where action is doing',
  ];

  $params['action_data'] = [
    'name' => 'action_data',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'Action data',
    'description' => 'it is array, fields depends on action and context',
  ];
}
