<?php

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * SqltasksActionTemplates.get API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_sqltasks_action_template_get($params) {
  try {
    $template = CRM_Sqltasks_BAO_SqltasksActionTemplate::getOne($params["id"]);

    if (empty($template)) {
      return civicrm_api3_create_success(null);
    }

    return civicrm_api3_create_success($template->mapToArray());
  } catch (\Exception $exception) {
    throw new API_Exception($exception->getMessage());
  }
}

/**
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_sqltasks_action_template_get_spec(&$spec) {
  $spec['id'] = [
    'name'         => 'id',
    'title'        => 'ID',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_INT,
    'description'  => 'Unique action template ID',
  ];
}


?>
