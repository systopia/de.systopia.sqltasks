<?php

/**
 * Gets list of message templates
 *
 * @return array
 */
function civicrm_api3_sqltaskfield_getmessagetemplates() {
  $messageTemplates = [];

  try {
    $messageTemplate = civicrm_api3('MessageTemplate', 'get', [
      'is_active' => 1,
      'return' => 'id,msg_title',
      'option.limit' => 0,
    ]);
  } catch (CiviCRM_API3_Exception $e) {}

  if (!empty($messageTemplate['values'])) {
    foreach ($messageTemplate['values'] as $template) {
      $messageTemplates[$template['id']] = $template['msg_title'];
    }
  }

  return civicrm_api3_create_success([$messageTemplates]);
}
