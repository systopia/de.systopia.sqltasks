<?php

/**
 * Get all supported task actions
 *
 * @return array
 * @throws \ReflectionException
 */
function civicrm_api3_sqltask_gettaskactions() {
  $actions = CRM_Sqltasks_Action::getAllActions();

  return civicrm_api3_create_success($actions);
}
