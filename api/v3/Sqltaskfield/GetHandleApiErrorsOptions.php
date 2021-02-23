<?php

/**
 * Gets list of 'handle api errors' options
 *
 * @return array
 */
function civicrm_api3_sqltaskfield_get_handle_api_errors_options() {
  return civicrm_api3_create_success([CRM_Sqltasks_Action_APICall::getHandleApiErrorsOptions()]);
}
