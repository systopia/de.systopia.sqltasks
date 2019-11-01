<?php

/**
 * Gets list of segmentation's exporters
 *
 * @return array
 */
function civicrm_api3_sqltaskfield_get_segmentation_exporter() {
  return civicrm_api3_create_success([CRM_Segmentation_Exporter::getExporterList()]);
}
