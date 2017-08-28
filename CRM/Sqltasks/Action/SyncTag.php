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
class CRM_Sqltasks_Action_SyncTag extends CRM_Sqltasks_Action_ContactSet {

  /**
   * Get identifier string
   */
  public function getID() {
    return 'tag';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('Synchronise Tag');
  }

  /**
   * Build the configuration UI
   */
  public function buildForm(&$form) {
    parent::buildForm($form);

    $form->add(
      'select',
      $this->getID() . '_tag_id',
      E::ts('Synchronise Tag'),
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
    $tag_id        = (int) $this->getConfigValue('tag_id');

    // first: remove the contacts that are NOT tagged
    error_log("
      DELETE FROM civicrm_entity_tag tag
      LEFT JOIN `{$contact_table}` ct ON ct.contact_id = tag.entity_id AND tag.entity_table='civicrm_contact'
      WHERE tag.tag_id = {$tag_id}
        AND ct.contact_id IS NULL");
    CRM_Core_DAO::executeQuery("
      DELETE FROM civicrm_entity_tag tag
      LEFT JOIN `{$contact_table}` ct ON ct.contact_id = tag.entity_id AND tag.entity_table='civicrm_contact'
      WHERE tag.tag_id = {$tag_id}
        AND ct.contact_id IS NULL");

    // then: add all missing contacts
    error_log("
      INSERT IGNORE INTO civicrm_entity_tag (entity_table, entity_id, tag_id)
        (SELECT
          'civicrm_contact'  AS entity_table,
          contact_id         AS entity_id,
          {$tag_id}          AS tag_id
        FROM {$contact_table}
        WHERE contact_id IS NOT NULL);");
    CRM_Core_DAO::executeQuery("
      INSERT IGNORE INTO civicrm_entity_tag (entity_table, entity_id, tag_id)
        (SELECT
          'civicrm_contact'  AS entity_table,
          contact_id         AS entity_id,
          {$tag_id}          AS tag_id
        FROM {$contact_table}
        WHERE contact_id IS NOT NULL);");
  }

  /**
   * Run the synchronisation via API
   */
  protected function executeAPI() {
    $contact_table = $this->getContactTable();

    // first: remove the ones that are NO in there


  }

  /**
   * get a list of eligible groups
   */
  protected function getEligibleGroups() {
    $tag_list = array();
    $tag_query = civicrm_api3('Tag', 'get', array(
      'is_enabled'   => 1,
      'option.limit' => 0,
      'return'       => 'id,name'))['values'];
    foreach ($tag_query as $tag) {
      $tag_list[$tag['id']] = CRM_Utils_Array::value('name', $tag, "Tag {$tag['id']}");
    }
    return $tag_list;
  }
}