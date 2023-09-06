<?php

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * SqltaskTemplate.Create API
 *
 * @param array $params
 *
 * @return array
 *
 * @throws API_Exception
 */
function civicrm_api3_sqltask_template_create($params) {
  try {
    $instance = CRM_Sqltasks_BAO_SqltasksTemplate::create($params);
    return civicrm_api3_create_success($instance->mapToArray());
  } catch (\Exception $exception) {
    throw new API_Exception($exception->getMessage());
  }
}

/**
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_sqltask_template_create_spec(&$spec) {
  $spec['id'] = [
    'name'         => 'id',
    'title'        => 'ID',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_INT,
    'description'  => 'Unique template ID',
  ];

  $spec['name'] = [
    'name'         => 'name',
    'title'        => 'Name',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'description'  => 'Name of the Template',
  ];

  $spec['description'] = [
    'name'         => 'description',
    'title'        => 'Description',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_STRING,
    'description'  => 'Additional information about the template',
  ];

  $spec['config'] = [
    'name'         => 'config',
    'title'        => 'Configuration',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'description'  => 'JSON task configuration object',
  ];
}

?>
