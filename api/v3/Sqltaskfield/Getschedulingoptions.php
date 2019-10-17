<?php

/**
 * Get scheduling options
 *
 * @param $params
 *
 * @return array
 * @throws \Exception
 */
function civicrm_api3_sqltaskfield_getschedulingoptions($params) {
    return civicrm_api3_create_success([CRM_Sqltasks_Task::getSchedulingOptions()]);
}
