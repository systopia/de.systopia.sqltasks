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
   * Get default template order
   *
   * @return int
   */
  public function getDefaultOrder() {
    return 500;
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
      $this->getEligibleTags(),
      FALSE,
      ['class' => 'crm-select2 huge']
    );

    $form->add(
      'select',
      $this->getID() . '_entity_table',
      E::ts('Choose Entity'),
      static::getEligibleEntities()
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
    $entity_table  = $this->getEntityTable();
    $tag_id        = (int) $this->getConfigValue('tag_id');

    $excludeSql = '';
    $excludeSqlWhere = '';
    if ($this->_columnExists($contact_table, 'exclude')) {
      $excludeSql = 'AND (exclude IS NULL OR exclude != 1)';
      $excludeSqlWhere = 'WHERE (exclude IS NULL OR exclude != 1)';
      $this->log('Column "exclude" exists, might skip some rows');
    }

    // first: remove the contacts that are NOT tagged
    CRM_Core_DAO::executeQuery("
      DELETE FROM civicrm_entity_tag
       WHERE tag_id = {$tag_id}
         AND entity_table = '{$entity_table}'
         AND entity_id NOT IN (SELECT contact_id FROM `{$contact_table}` {$excludeSqlWhere})");

    // then: add all missing contacts
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_entity_tag (entity_table, entity_id, tag_id)
        (SELECT
          '{$entity_table}'  AS entity_table,
          contact_id         AS entity_id,
          {$tag_id}          AS tag_id
        FROM {$contact_table}
        WHERE contact_id IS NOT NULL {$excludeSql})
        ON DUPLICATE KEY UPDATE
          civicrm_entity_tag.id = civicrm_entity_tag.id");
  }

  /**
   * Run the synchronisation via API
   */
  protected function executeAPI() {
    $contact_table = $this->getContactTable();
    $entity_table  = $this->getEntityTable();
    $tag_id        = (int) $this->getConfigValue('tag_id');

    $excludeSql = '';
    $excludeSqlWhere = '';
    if ($this->_columnExists($contact_table, 'exclude')) {
      $excludeSql = 'AND (exclude IS NULL OR exclude != 1)';
      $excludeSqlWhere = 'WHERE (exclude IS NULL OR exclude != 1)';
      $this->log('Column "exclude" exists, might skip some rows');
    }

    // first: remove the ones that are NO in there
    $tags2remove = CRM_Core_DAO::executeQuery("
      SELECT entity_id AS contact_id
        FROM civicrm_entity_tag
       WHERE tag_id = {$tag_id}
         AND entity_table = '{$entity_table}'
         AND entity_id NOT IN (SELECT contact_id FROM `{$contact_table}` {$excludeSqlWhere})");
    while ($tags2remove->fetch()) {
      civicrm_api3('EntityTag', 'delete',
        array('contact_id' => $tags2remove->contact_id,
              'tag_id'     => $tag_id));
    }

    // then: add the new ones
    $tags2add = CRM_Core_DAO::executeQuery("
      SELECT DISTINCT contact_id
      FROM `{$contact_table}`
      LEFT JOIN civicrm_entity_tag et ON  et.entity_id = contact_id
                                      AND et.entity_table = '{$entity_table}'
                                      AND et.tag_id = {$tag_id}
      WHERE contact_id IS NOT NULL
        AND et.entity_id IS NULL {$excludeSql}");

    while ($tags2add->fetch()) {
      civicrm_api3('EntityTag', 'create', array(
        'entity_id'    => $tags2add->contact_id,
        'entity_table' => $entity_table,
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
      $tag_list[$tag['id']] = CRM_Utils_Array::value('name', $tag, 'Tag') . ' [' . $tag['id'] . ']';
    }
    return $tag_list;
  }

  /**
   * Get a list of eligible groups
   */
  public static function getEligibleEntities() {
    return array(
      'civicrm_contact'      => E::ts("Contacts"),
      'civicrm_activity'     => E::ts("Activities"),
      'civicrm_case'         => E::ts("Cases"),
      'civicrm_file'         => E::ts("Attachments"),
      'civicrm_membership'   => E::ts("Memberships"),
      'civicrm_contribution' => E::ts("Contributions"),
    );
  }

  /**
   * get the entity table to use for the tag
   *
   * defaults to 'civicrm_contact'
   */
  public function getEntityTable() {
    $table_name = $this->getConfigValue('entity_table');
    $table_name = trim($table_name);
    if (empty($table_name)) {
      return 'civicrm_contact';
    } else {
      return $table_name;
    }
  }
}
