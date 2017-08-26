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
 * This actions allows you to synchronise
 *  a resulting contact set with a group
 *
 */
class CRM_Sqltasks_Action_SyncGroup extends CRM_Sqltasks_Action_ContactSet {

  /**
   * Get identifier string
   */
  public function getID() {
    return 'group';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('Synchronise Group');
  }

  /**
   * Build the configuration UI
   */
  public function buildForm(&$form) {
    parent::buildForm($form);

    $form->add(
      'select',
      $this->getID() . '_group_id',
      E::ts('Synchronise Group'),
      $this->getEligibleGroups()
    );

    $form->add(
      'checkbox',
      $this->getID() . '_use_api',
      E::ts('Use API (slow)')
    );
  }

  /**
   * RUN this action
   */
  public function execute() {
    error_log("EXECUTE!!!");
  }

  /**
   * get a list of eligible groups
   */
  protected function getEligibleGroups() {
    $group_list = array();
    $group_query = civicrm_api3('Group', 'get', array(
      'is_enabled'   => 1,
      'option.limit' => 0,
      'return'       => 'id,name'))['values'];
    foreach ($group_query as $group) {
      $group_list[$group['id']] = CRM_Utils_Array::value('name', $group, "Group {$group['id']}");
    }
    return $group_list;
  }
}