<?php

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * SqltaskTemplate.Getall API
 *
 * @param array $params
 *
 * @return array $templates
 *
 * @throws API_Exception
 */
function civicrm_api3_sqltask_template_get_all($params) {
  try {
    $templates = array_map(
      function ($bao) { return $bao->mapToArray(); },
      CRM_Sqltasks_BAO_SqltasksTemplate::getAll()
    );

    return civicrm_api3_create_success($templates);
  } catch (\Exception $exception) {
    throw new API_Exception($exception->getMessage());
  }
}

/**
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_sqltask_template_get_all_spec(&$spec) {
  // no parameters
}

?>
