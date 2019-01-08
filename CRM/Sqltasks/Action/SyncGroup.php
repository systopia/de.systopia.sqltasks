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
    $use_api = $this->getConfigValue('use_api');
    if ($use_api) {
      $this->executeAPI();
    } else {
      $this->executeSQL();
    }
  }

  /**
   * Run the synchronisation purely by SQL
   */
  protected function executeSQL() {
    $contact_table = $this->getContactTable();
    $group_id      = (int) $this->getConfigValue('group_id');
    $now           = date('YmdHis');

    $excludeSql = '';
    $excludeSqlWhere = '';
    if ($this->_columnExists($contact_table, 'exclude')) {
      $excludeSql = 'AND (exclude IS NULL OR exclude != 1)';
      $excludeSqlWhere = 'WHERE (exclude IS NULL OR exclude != 1)';
      $this->log('Column "exclude" exists, might skip some rows');
    }

    // 1. add the contacts, that have never been added
    // 1.1. subscription history
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_subscription_history (group_id, contact_id, date, method, status)
        (SELECT DISTINCT
          {$group_id}        AS group_id,
          contact_id         AS contact_id,
          '{$now}'           AS date,
          NULL               AS method,
          'Added'            AS status
        FROM {$contact_table}
        WHERE contact_id NOT IN (SELECT contact_id
                                   FROM civicrm_group_contact
                                  WHERE group_id = {$group_id}) {$excludeSql});");

    // 1.2. actual group
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_group_contact (group_id, contact_id, status)
        (SELECT DISTINCT
          {$group_id}        AS group_id,
          contact_id         AS contact_id,
          'Added'            AS status
        FROM {$contact_table}
        WHERE contact_id NOT IN (SELECT contact_id
                                   FROM civicrm_group_contact
                                  WHERE group_id = {$group_id}) {$excludeSql})
        ON DUPLICATE KEY UPDATE
          civicrm_group_contact.id = civicrm_group_contact.id");

    // 2. update the ones that have been previously removed
    // 2.1. subscription history
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_subscription_history (group_id, contact_id, date, method, status)
        (SELECT DISTINCT
          {$group_id}        AS group_id,
          contact_id         AS contact_id,
          '{$now}'           AS date,
          NULL               AS method,
          'Added'            AS status
        FROM {$contact_table}
        WHERE contact_id IN (SELECT contact_id
                               FROM civicrm_group_contact
                              WHERE group_id = {$group_id}
                                AND status = 'Removed') {$excludeSql});");

    // 2.1. actual group
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_group_contact
         SET status = 'Added'
       WHERE group_id = {$group_id}
         AND status = 'Removed'
         AND contact_id IN (SELECT contact_id
                               FROM {$contact_table} {$excludeSqlWhere})");


    // 3. remove the ones that are not in the list any more
    // 3.1. subscription history
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_subscription_history (group_id, contact_id, date, method, status)
        (SELECT
          {$group_id}        AS group_id,
          contact_id         AS contact_id,
          '{$now}'           AS date,
          NULL               AS method,
          'Removed'          AS status
        FROM civicrm_group_contact
        WHERE group_id = {$group_id}
          AND status = 'Added'
          AND contact_id NOT IN (SELECT contact_id
                                 FROM {$contact_table} {$excludeSqlWhere}))");

    // 3.2. actual group
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_group_contact
         SET status = 'Removed'
       WHERE group_id = {$group_id}
         AND status = 'Added'
         AND contact_id NOT IN (SELECT contact_id
                                  FROM {$contact_table} {$excludeSqlWhere})");

  }

  /**
   * Run the synchronisation via API
   */
  protected function executeAPI() {
    $contact_table = $this->getContactTable();
    $group_id      = (int) $this->getConfigValue('group_id');

    $excludeSql = '';
    if ($this->_columnExists($contact_table, 'exclude')) {
      $excludeSql = "AND (result.exclude IS NULL OR result.exclude != 1)";
      $this->log('Column "exclude" exists, might skip some rows');
    }

    // first: remove the ones not in the new list
    $contacts2remove = CRM_Core_DAO::executeQuery("
      SELECT civicrm_group_contact.contact_id AS contact_id
      FROM civicrm_group_contact
      LEFT JOIN `{$contact_table}` result ON civicrm_group_contact.contact_id = result.contact_id
      WHERE civicrm_group_contact.group_id = {$group_id}
        AND civicrm_group_contact.status = 'Added'
        AND result.contact_id IS NULL {$excludeSql}");
    while ($contacts2remove->fetch()) {
      civicrm_api3('GroupContact', 'create', array(
        'contact_id'        => $contacts2remove->contact_id,
        'group_id'          => $group_id,
        'status'            => 'Removed'));
    }


    // then: add the new ones
    $contacts2add = CRM_Core_DAO::executeQuery("
      SELECT DISTINCT result.contact_id AS contact_id
      FROM `{$contact_table}` result
      LEFT JOIN civicrm_group_contact ON civicrm_group_contact.contact_id = result.contact_id
                                      AND civicrm_group_contact.group_id = {$group_id}
      WHERE (civicrm_group_contact.status IS NULL OR civicrm_group_contact.status = 'Removed')
        AND result.contact_id IS NOT NULL {$excludeSql}");
    while ($contacts2add->fetch()) {
      civicrm_api3('GroupContact', 'create', array(
        'contact_id'        => $contacts2add->contact_id,
        'group_id'          => $group_id));
    }
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