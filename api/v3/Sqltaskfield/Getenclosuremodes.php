<?php

/**
 * Gets list of CSV field enclosure options
 *
 * @return array
 */
function civicrm_api3_sqltaskfield_getenclosuremodes() {
  return civicrm_api3_create_success([CRM_Sqltasks_Action_CSVExport::getEnclosureModes()]);
}
