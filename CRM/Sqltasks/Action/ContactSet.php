<?php
/*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * Abstract, contact set based action
 */
abstract class CRM_Sqltasks_Action_ContactSet extends CRM_Sqltasks_Action {

  /**
   * Build the configuration UI
   */
  public function buildForm(&$form) {
    parent::buildForm($form);

    $form->add(
      'text',
      $this->getID() . '_contact_table',
      E::ts('Contact Table (<code>contact_id</code>)'),
      ['style' => 'font-family: monospace, monospace !important']
    );
  }

  /**
   * get the table with the contact_id column
   */
  public function getContactTable() {
    $table_name = $this->getConfigValue('contact_table');
    $this->resolveTableToken($table_name);
    return trim($table_name);
  }


  /**
   * Check if this action is configured correctly
   */
  public function checkConfiguration() {
    parent::checkConfiguration();

    $contact_table = $this->getContactTable();
    if (empty($contact_table)) {
      throw new Exception("Contact Table not configured.", 1);
    }

    // check if table exists

    $existing_table = CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '{$contact_table}';");
    if (!$existing_table) {
      throw new Exception("Contact Table '{$contact_table}' doesn't exist.", 1);
    }

    $existing_column = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$contact_table}` LIKE 'contact_id';");
    if (!$existing_column) {
      throw new Exception("Contact Table '{$contact_table}' doesn't have a column 'contact_id'.", 1);
    }
  }
}