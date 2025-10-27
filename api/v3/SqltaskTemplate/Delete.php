<?php

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * SqltaskTemplate.Delete API
 *
 * @param array $params
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_sqltask_template_delete($params) {
  try {
    CRM_Sqltasks_BAO_SqltasksTemplate::deleteOne($params["id"]);
    return civicrm_api3_create_success();
  } catch (\Exception $exception) {
    throw new CRM_Core_Exception($exception->getMessage());
  }
}

/**
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_sqltask_template_delete_spec(&$spec) {
  $spec['id'] = [
    'name'         => 'id',
    'title'        => 'ID',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_INT,
    'description'  => 'Unique template ID',
  ];
}

?>
