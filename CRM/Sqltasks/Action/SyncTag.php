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
      $this->getEligibleTags()
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
    CRM_Core_DAO::executeQuery("
      DELETE FROM civicrm_entity_tag
       WHERE tag_id = {$tag_id}
         AND entity_table = 'civicrm_contact'
         AND entity_id NOT IN (SELECT contact_id FROM `{$contact_table}`);");

    // then: add all missing contacts
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
    $tag_id        = (int) $this->getConfigValue('tag_id');

    // first: remove the ones that are NO in there
    $tags2remove = CRM_Core_DAO::executeQuery("
      SELECT entity_id AS contact_id
        FROM civicrm_entity_tag
       WHERE tag_id = {$tag_id}
         AND entity_table = 'civicrm_contact'
         AND entity_id NOT IN (SELECT contact_id FROM `{$contact_table}`);");
    while ($tags2remove->fetch()) {
      civicrm_api3('EntityTag', 'delete',
        array('contact_id' => $tags2remove->contact_id,
              'tag_id'     => $tag_id));
    }

    // then: add the new ones
    $tags2add = CRM_Core_DAO::executeQuery("
      SELECT contact_id
      FROM `{$contact_table}`
      LEFT JOIN civicrm_entity_tag et ON  et.entity_id = contact_id
                                      AND et.entity_table = 'civicrm_contact'
                                      AND et.tag_id = {$tag_id}
      WHERE contact_id IS NOT NULL
        AND et.entity_id IS NULL;");

    while ($tags2add->fetch()) {
      civicrm_api3('EntityTag', 'create', array(
        'entity_id'    => $tags2add->contact_id,
        'entity_table' => 'civicrm_contact',
        'tag_id'       => $tag_id));
    }
  }

  /**
   * get a list of eligible groups
   */
  protected function getEligibleTags() {
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