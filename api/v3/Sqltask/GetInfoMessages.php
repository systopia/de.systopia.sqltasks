<?php

use Civi\Utils\Sqltasks\InfoMessages;

/**
 * Get all info messages for sqltask manager
 */
function civicrm_api3_sqltask_get_info_messages() {
  return civicrm_api3_create_success((new InfoMessages())->getAll());
}
