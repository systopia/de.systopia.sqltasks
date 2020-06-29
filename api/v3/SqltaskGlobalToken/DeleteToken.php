<?php

/**
 * Deletes global token by name
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltask_global_token_delete_token($params) {
  $globalToken = CRM_Sqltasks_GlobalToken::singleton();
  $globalToken->delete($params['name']);

  return civicrm_api3_create_success(['The token doesn\'t exist anymore.']);
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_global_token_delete_token_spec(&$params) {
  $params['name'] = [
    'name' => 'name',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'Token name',
    'description'  => 'This token will be deleted',
  ];
}
