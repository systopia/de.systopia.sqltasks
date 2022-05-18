<?php

/**
 * Gets list of campaign statuses
 *
 * @return array
 */
function civicrm_api3_sqltaskfield_get_campaign_statuses() {
  return civicrm_api3_create_success([CRM_Sqltasks_Action_SegmentationAssign::getCampaignStatusOptions()]);
}
