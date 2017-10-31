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

define('CUSTOM_SEGMENT_ID', 999999);

/**
 * This action allows you to export campaign contacts
 *  if you have de.systopia.segmentation installed
 *
 * @see https://github.com/systopia/de.systopia.segmentation
 */
class CRM_Sqltasks_Action_SegmentationAssign extends CRM_Sqltasks_Action {

  protected static $_assignment_timestamp = NULL;
  protected static $_assignment_task_id   = NULL;

  /**
   * Get identifier string
   */
  public function getID() {
    return 'segmentation_assign';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('Assign to Campaign (Segmentation)');
  }

  /**
   * Build the configuration UI
   */
  public function buildForm(&$form) {
    parent::buildForm($form);

    $form->add(
      'text',
      $this->getID() . '_table',
      E::ts('Data Table'),
      array()
    );

    $form->add(
      'select',
      $this->getID() . '_campaign_id',
      E::ts('Campaign'),
      $this->getEligibleCampaigns(),
      FALSE,
      array('class' => 'crm-select2 huge')
    );

    $form->add(
      'text',
      $this->getID() . '_segment_name',
      E::ts('Segment'),
      array('class' => 'huge')
    );

    $form->add(
      'checkbox',
      $this->getID() . '_segment_from_table',
      E::ts('Segment from data table')
    );

    // pass on specs for the 'custom segment' option
    $form->assign($this->getID() . '_custom_segment_label', E::ts('[<i>from data table</i>: <code>segment_name</code>]'));
    $form->assign($this->getID() . '_custom_segment_id', CUSTOM_SEGMENT_ID);
  }

  /**
   * Check if this action is configured correctly
   */
  public function checkConfiguration() {
    parent::checkConfiguration();

    $data_table = $this->getDataTable();
    if (empty($data_table)) {
      throw new Exception("Data table not configured.", 1);
    }

    // check if table exists
    $existing_table = CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '{$data_table}';");
    if (!$existing_table) {
      throw new Exception("Data table '{$data_table}' doesn't exist.", 1);
    }

    // check if table has contact_id or membership_id
    $contact_column    = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$data_table}` LIKE 'contact_id';");
    $membership_column = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$data_table}` LIKE 'membership_id';");
    if (!$contact_column && !$membership_column) {
      throw new Exception("Data table '{$data_table}' has neither 'contact_id' nor 'membership_id'.", 1);
    }


    // check segments
    $segment_from_table = $this->getConfigValue('segment_from_table');
    if ($segment_from_table) {
      // check if table has a segment_name column
      $segment_colum = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$data_table}` LIKE 'segment_name';");
      if (!$segment_colum) {
        throw new Exception("Data table '{$data_table}' has no column 'segment_name'.", 1);
      }
    } else {
      $segment_name = $this->getConfigValue('segment_name');
      if (empty($segment_name)) {
        throw new Exception("No segment name given", 1);
      }
    }

    // check campaign
    $campaign_id = $this->getConfigValue('campaign_id');
    if (!$campaign_id) {
      throw new Exception("No campaign selected", 1);
    }
  }

  /**
   * get the table with the contact_id column
   */
  protected function getDataTable() {
    $table_name = $this->getConfigValue('table');
    return trim($table_name);
  }


  /**
   * RUN this action
   */
  public function execute() {
    // get some basic data
    $timestamp   = date('Y-m-d H:i:s');
    $campaign_id = $this->getConfigValue('campaign_id');
    $data_table  = $this->getDataTable();
    $task_id     = $this->task->getID();
    $temp_table  = "temp_segmentation_sqltask{$task_id}_assignment_cache";
    $membership_column = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$data_table}` LIKE 'membership_id';");

    // RESOLVE
    $segment_name_2_id = array();
    $segment_from_table = $this->getConfigValue('segment_from_table');
    if ($segment_from_table) {
      $segment_query = CRM_Core_DAO::executeQuery("SELECT DISTINCT(segment_name) AS segment_name FROM '{$data_table}'");
      while ($segment_query->fetch()) {
        $segment_name_2_id[$segment_query->segment_name] = 0;
      }

    } else {
      $segment_name = $this->getConfigValue('segment_name');
      $segment_name_2_id[$segment_name] = 0;
    }

    // resolve each segment individually
    $segment_count = count($segment_name_2_id);
    foreach ($segment_name_2_id as $segment_name => &$segment_id) {
      $segment = civicrm_api3('Segmentation', 'getsegmentid', array('name' => $segment_name));
      $segment_id = $segment['id'];
    }
    $this->log("Resolved {$segment_count} segment(s).");


    // ASSIGN CONTACT/MEMBERSHIPS
    foreach ($segment_name_2_id as $segment_name => $segment_id) {
      // prepare query parameters
      $params = array(1 => array($campaign_id,  'Integer'),
                      2 => array($segment_id,   'Integer'),
                      3 => array($timestamp,    'String'));

      if ($segment_from_table) {
        // segments taken from table
        $segment_filter = "WHERE `{$data_table}`.segment_name = %4";
        $params[4] = array($segment_name, 'String');
      } else {
        // static segments
        $segment_filter = '';
      }

      CRM_Core_DAO::executeQuery("DROP TEMPORARY TABLE IF EXISTS `{$temp_table}`;");
      if ($membership_column) {
        // ASSIGN MEMBERSHIPS (multi-segment)
        // create temp table
        CRM_Core_DAO::executeQuery("
            CREATE TEMPORARY TABLE `{$temp_table}` AS
            SELECT civicrm_membership.contact_id AS entity_id,
                   %3                            AS datetime,
                   %1                            AS campaign_id,
                   %2                            AS segment_id,
                   NULL                          AS test_group,
                   `{$data_table}`.membership_id AS membership_id,
                   civicrm_segmentation.id       AS already_assigned
            FROM `{$data_table}`
            LEFT JOIN civicrm_membership   ON civicrm_membership.id = `{$data_table}`.membership_id
            LEFT JOIN civicrm_segmentation ON civicrm_segmentation.campaign_id    = %1
                                           AND civicrm_segmentation.membership_id = civicrm_membership.id
                                           AND civicrm_segmentation.segment_id    = %2
            {$segment_filter}", $params);
        // get count
        $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM `{$temp_table}` WHERE already_assigned IS NULL;");
        // assign memberships
        CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO `civicrm_segmentation` (entity_id, datetime, campaign_id, segment_id, test_group, membership_id)
          SELECT entity_id, datetime, campaign_id, segment_id, test_group, membership_id
          FROM `{$temp_table}`
          WHERE already_assigned IS NULL;");

        $this->log("Assigned {$count} new memberships to segment '{$segment_name}'.");

      } else {
        // ASSIGN CONTACTS (multi-segment)
        // create temp table
        CRM_Core_DAO::executeQuery("
            CREATE TEMPORARY TABLE `{$temp_table}` AS
            SELECT contact_id              AS entity_id,
                   %3                      AS datetime,
                   %1                      AS campaign_id,
                   %2                      AS segment_id,
                   NULL                    AS test_group,
                   NULL                    AS membership_id,
                   civicrm_segmentation.id AS already_assigned
            FROM `{$data_table}`
            LEFT JOIN civicrm_segmentation ON civicrm_segmentation.campaign_id = %1
                                           AND civicrm_segmentation.entity_id  = `{$data_table}`.contact_id
                                           AND civicrm_segmentation.segment_id = %2
            {$segment_filter}", $params);
        // get count
        $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM `{$temp_table}` WHERE already_assigned IS NULL;");
        // assign memberships
        CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO `civicrm_segmentation` (entity_id, datetime, campaign_id, segment_id, test_group, membership_id)
          SELECT entity_id, datetime, campaign_id, segment_id, test_group, membership_id
          FROM `{$temp_table}`
          WHERE already_assigned IS NULL;");
        $this->log("Assigned {$count} new contacts to segment '{$segment_name}'.");
      }

      // cleanup
      CRM_Core_DAO::executeQuery("DROP TEMPORARY TABLE IF EXISTS `{$temp_table}`;");
    }

    // store assignment start/end dates
    self::$_assignment_timestamp  = $timestamp;
    self::$_assignment_task_id    = $this->task->getID();
  }


  /**
   * Get the start date of the current assignment
   */
  public static function getAssignmentTimestamp($task_id) {
    if (self::$_assignment_task_id == $task_id) {
      return self::$_assignment_timestamp;
    } else {
      return NULL;
    }
  }
}