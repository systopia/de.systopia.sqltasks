<?php

/**
 * Gets list of file encoding options
 *
 * @return array
 */
function civicrm_api3_sqltaskfield_getfileencoding() {
  return civicrm_api3_create_success([CRM_Sqltasks_Action_CSVExport::getEncodingOptions()]);
}
