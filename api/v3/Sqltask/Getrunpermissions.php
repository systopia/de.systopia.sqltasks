<?php

/**
 * Get list of availables permission for run task
 *
 * @return array
 */
function civicrm_api3_sqltask_getrunpermissions() {
  $permissions = CRM_Core_Permission::basicPermissions(TRUE);

  return civicrm_api3_create_success([$permissions]);
}
