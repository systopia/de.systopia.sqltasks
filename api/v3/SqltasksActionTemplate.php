<?php

declare(strict_types = 1);

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * SqltasksActionTemplate.create API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_sqltasks_action_template_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'SqltasksActionTemplate');
}

/**
 * SqltasksActionTemplate.delete API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_sqltasks_action_template_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * SqltasksActionTemplate.get API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_sqltasks_action_template_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'SqltasksActionTemplate');
}
