<?php

/**
 * Get list of entities for "Sync group" task action
 *
 * @return array
 */
function civicrm_api3_sqltaskfield_getsynctagentities() {
  return civicrm_api3_create_success([CRM_Sqltasks_Action_SyncTag::getEligibleEntities()]);
}
