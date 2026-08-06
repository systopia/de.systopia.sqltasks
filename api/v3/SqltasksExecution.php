<?php

declare(strict_types = 1);

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * SqltasksExecution.create API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_sqltasks_execution_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'SqltasksExecution');
}

/**
 * SqltasksExecution.delete API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_sqltasks_execution_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * SqltasksExecution.get API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_sqltasks_execution_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'SqltasksExecution');
}
