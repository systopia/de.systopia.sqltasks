<?php

/**
 * Update token data
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltask_global_token_update_token($params) {
  $globalToken = CRM_Sqltasks_GlobalToken::singleton();

  if (!$globalToken->isTokenExist($params['name'])) {
    return civicrm_api3_create_error("This token with this name does not exist.");
  }

  if (!empty($params['new_name'])) {
    if ($params['new_name'] != $params['name']) {
      if ($globalToken->isTokenExist($params['new_name'])) {
        return civicrm_api3_create_error(ts("Token name is already exists."));
      }

      if (strlen($params['new_name']) > CRM_Sqltasks_GlobalToken::MAX_LENGTH_OF_TOKEN_NAME) {
        return civicrm_api3_create_error(ts("Max length of token name is %1", ['1' => CRM_Sqltasks_GlobalToken::MAX_LENGTH_OF_TOKEN_NAME] ));
      }

      $globalToken->delete($params['name']);
    }

    $globalToken->setValue($params['new_name'], $params['value']);

    return civicrm_api3_create_success([$globalToken->getTokenData($params['new_name'])]);
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
function _civicrm_api3_sqltask_global_token_update_token_spec(&$params) {
  $params['name'] = [
    'name' => 'name',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'Token name',
    'description'  => 'Token which will be updated',
  ];

  $params['new_name'] = [
    'name' => 'new_name',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'New token name',
  ];

  $params['value'] = [
    'name' => 'value',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'New token value',
  ];
}
