<?php

/**
 * Creates new global token
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltask_global_token_create($params) {
  $globalToken = CRM_Sqltasks_GlobalToken::singleton();

  if ($globalToken->isTokenExist($params['name'])) {
    return civicrm_api3_create_error(ts("Token name is already exists."));
  }

  if (!isset($params['value'])) {
    $params['value'] = '';
  }

  if (strlen($params['name']) > CRM_Sqltasks_GlobalToken::MAX_LENGTH_OF_TOKEN_NAME) {
    return civicrm_api3_create_error(ts("Max length of token name is %1", ['1' => CRM_Sqltasks_GlobalToken::MAX_LENGTH_OF_TOKEN_NAME] ));
  }

  $globalToken->setValue($params['name'], $params['value']);

  return civicrm_api3_create_success([$globalToken->getTokenData($params['name'])]);
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_global_token_create_spec(&$params) {
  $params['name'] = [
    'name' => 'name',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'Token name',
    'description'  => 'The value of this name will be updated.',
  ];

  $params['value'] = [
    'name' => 'value',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'Token value',
    'description'  => 'New token value.',
  ];
}
