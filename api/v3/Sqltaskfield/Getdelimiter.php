<?php

/**
 * Gets list of delimiter
 *
 * @return array
 */
function civicrm_api3_sqltaskfield_getdelimiter() {
  return civicrm_api3_create_success([CRM_Sqltasks_Action_CSVExport::getDelimiterOptions()]);
}
