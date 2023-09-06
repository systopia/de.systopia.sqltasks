<?php

/**
 * Gets sqltask token/tokens
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltask_global_token_get($params) {
  $globalToken = CRM_Sqltasks_GlobalToken::singleton();

  if (empty($params['name'])) {
    return civicrm_api3_create_success($globalToken->getAllTokenData());
  }

  return civicrm_api3_create_success([$globalToken->getTokenData($params['name'])]);
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_global_token_get_spec(&$params) {
  $params['name'] = [
    'name' => 'name',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'Token name',
  ];
}
