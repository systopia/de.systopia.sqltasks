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
   * Get default template order
   *
   * @return int
   */
  public function getDefaultOrder() {
    return 100;
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
      ['style' => 'font-family: monospace, monospace !important']
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
      $this->getID() . '_clear',
      E::ts('Clear before assignment')
    );

    $form->add(
      'checkbox',
      $this->getID() . '_segment_from_table',
      E::ts('Segment from data table')
    );

    $form->add(
      'select',
      $this->getID() . '_start',
      E::ts('Change Campaign Status'),
      static::getCampaignStatusOptions(),
      FALSE,
      array('class' => 'crm-select2 huge')
    );

    $form->add(
      'textarea',
      $this->getID() . '_segment_order',
      E::ts('Segment Order'),
      array('rows' => 8, 'cols' => 40, 'style' => 'font-family: monospace, monospace !important'),
      FALSE
    );

    $form->add(
      'text',
      $this->getID() . '_segment_order_table',
      E::ts('Segment Order Table'),
      array('class' => 'huge', 'style' => 'font-family: monospace, monospace !important')
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

    // check segmentation order
    $status_change = $this->getConfigValue('start');
    if ($status_change == 'restart_t') {
      // get the order from the table
      $table_name = $this->getConfigValue('segment_order_table');
      $this->resolveTableToken($table_name);
      $segment_colum = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$table_name}` LIKE 'segment_name';");
      if (!$segment_colum) {
        throw new Exception("Segmentation order table '{$table_name}' has no column 'segment_name'.", 1);
      }
      $segment_colum = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$table_name}` LIKE 'segment_weight';");
      if (!$segment_colum) {
        throw new Exception("Segmentation order table '{$table_name}' has no column 'segment_weight'.", 1);
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
    $this->resolveTableToken($table_name);
    return trim($table_name);
  }


  /**
   * RUN this action
   */
  public function execute() {
    // get some basic data
    $this->resetHasExecuted();
    $timestamp   = date('Y-m-d H:i:s');
    $campaign_id = $this->getConfigValue('campaign_id');
    $data_table  = $this->getDataTable();
    $task_id     = $this->task->getID();
    $temp_table  = "temp_sqltask{$task_id}_assign_" . substr(microtime(), 2, 8);
    $membership_column = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$data_table}` LIKE 'membership_id';");

    // CLEAR (if requested)
    $clear = $this->getConfigValue('clear');
    if ($clear) {
      // clear out campaign
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_segmentation WHERE campaign_id = %1",
        array(1 => array($campaign_id, 'Integer')));
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_segmentation_exclude WHERE campaign_id = %1",
        array(1 => array($campaign_id, 'Integer')));
      $this->log("Cleared out campaign [{$campaign_id}]");
    }

    // RESOLVE
    $segment_name_2_id = array();
    $segment_from_table = $this->getConfigValue('segment_from_table');
    if ($segment_from_table) {
      $segment_query = CRM_Core_DAO::executeQuery("SELECT DISTINCT(segment_name) AS segment_name FROM `{$data_table}`");
      while ($segment_query->fetch()) {
        $segment_name_2_id[$segment_query->segment_name] = 0;
      }

    } else {
      $segment_name = $this->getConfigValue('segment_name');
      $segment_name_2_id[$segment_name] = 0;
    }

    // resolve each segment individually
    $segment_count = count($segment_name_2_id);
    foreach (array_keys($segment_name_2_id) as $segment_name) {
      $segment = civicrm_api3('Segmentation', 'getsegmentid', array('name' => $segment_name));
      $segment_name_2_id[$segment_name] = $segment['id'];
    }
    $this->log("Resolved {$segment_count} segment(s).");

    $excludeSql = '0 AS exclude';
    if ($this->_columnExists($data_table, 'exclude')) {
      $excludeSql = "`{$data_table}`.exclude AS exclude";
      $this->log('Column "exclude" exists, might skip some rows');
    }

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
            SELECT civicrm_membership.contact_id   AS entity_id,
                   %3                              AS datetime,
                   %1                              AS campaign_id,
                   %2                              AS segment_id,
                   NULL                            AS test_group,
                   `{$data_table}`.membership_id   AS membership_id,
                   civicrm_segmentation.id         AS already_assigned,
                   civicrm_segmentation_exclude.id AS already_excluded,
                   {$excludeSql}
            FROM `{$data_table}`
            LEFT JOIN civicrm_membership           ON civicrm_membership.id = `{$data_table}`.membership_id
            LEFT JOIN civicrm_segmentation         ON civicrm_segmentation.campaign_id    = %1
                                                   AND civicrm_segmentation.membership_id = civicrm_membership.id
                                                   AND civicrm_segmentation.segment_id    = %2
            LEFT JOIN civicrm_segmentation_exclude ON civicrm_segmentation_exclude.campaign_id    = %1
                                                   AND civicrm_segmentation_exclude.membership_id = civicrm_membership.id
                                                   AND civicrm_segmentation_exclude.segment_id    = %2
            {$segment_filter}", $params);
        // get count
        $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM `{$temp_table}` WHERE already_assigned IS NULL AND (exclude IS NULL or exclude = 0)");
        // assign memberships
        CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO `civicrm_segmentation` (entity_id, datetime, campaign_id, segment_id, test_group, membership_id)
          SELECT entity_id, datetime, campaign_id, segment_id, test_group, membership_id
          FROM `{$temp_table}`
          WHERE already_assigned IS NULL AND (exclude IS NULL or exclude = 0)");
        $this->log("Assigned {$count} new memberships to segment '{$segment_name}'.");
        if ($count) {
          $this->setHasExecuted();
        }

        // handle exclusions
        $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM `{$temp_table}` WHERE already_excluded IS NULL AND exclude = 1");
        CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO `civicrm_segmentation_exclude` (campaign_id, segment_id, contact_id, membership_id, created_date)
          SELECT campaign_id, segment_id, entity_id, membership_id, datetime
          FROM `{$temp_table}`
          WHERE already_excluded IS NULL AND exclude = 1");
        $this->log("Excluded {$count} new memberships from segment '{$segment_name}'.");



      } else {
        // ASSIGN CONTACTS (multi-segment)
        // create temp table
        CRM_Core_DAO::executeQuery("
            CREATE TEMPORARY TABLE `{$temp_table}` AS
            SELECT `{$data_table}`.contact_id      AS entity_id,
                   %3                              AS datetime,
                   %1                              AS campaign_id,
                   %2                              AS segment_id,
                   NULL                            AS test_group,
                   NULL                            AS membership_id,
                   civicrm_segmentation.id         AS already_assigned,
                   civicrm_segmentation_exclude.id AS already_excluded,
                   {$excludeSql}
            FROM `{$data_table}`
            LEFT JOIN civicrm_segmentation ON civicrm_segmentation.campaign_id = %1
                                           AND civicrm_segmentation.entity_id  = `{$data_table}`.contact_id
                                           AND civicrm_segmentation.segment_id = %2
            LEFT JOIN civicrm_segmentation_exclude ON civicrm_segmentation_exclude.campaign_id = %1
                                           AND civicrm_segmentation_exclude.contact_id  = `{$data_table}`.contact_id
                                           AND civicrm_segmentation_exclude.segment_id = %2
            {$segment_filter}", $params);
        // get count
        $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM `{$temp_table}` WHERE already_assigned IS NULL AND (exclude IS NULL or exclude = 0)");
        // assign memberships
        CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO `civicrm_segmentation` (entity_id, datetime, campaign_id, segment_id, test_group, membership_id)
          SELECT entity_id, datetime, campaign_id, segment_id, test_group, membership_id
          FROM `{$temp_table}`
          WHERE already_assigned IS NULL AND (exclude IS NULL or exclude = 0)");
        $this->log("Assigned {$count} new contacts to segment '{$segment_name}'.");
        if ($count) {
          $this->setHasExecuted();
        }

        // handle exclusions
        $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM `{$temp_table}` WHERE already_excluded IS NULL AND exclude = 1");
        CRM_Core_DAO::executeQuery("
          INSERT IGNORE INTO `civicrm_segmentation_exclude` (campaign_id, segment_id, contact_id, membership_id, created_date)
          SELECT campaign_id, segment_id, entity_id, membership_id, datetime
          FROM `{$temp_table}`
          WHERE already_excluded IS NULL AND exclude = 1");
        $this->log("Excluded {$count} new contacts from segment '{$segment_name}'.");
      }

      // cleanup
      CRM_Core_DAO::executeQuery("DROP TEMPORARY TABLE IF EXISTS `{$temp_table}`;");

    }

    // CHANGE STATUS
    $status_change = $this->getConfigValue('start');
    $new_status = NULL;
    switch ($status_change) {
      case 'planned':
        # reset status to planned
        $has_changed = $this->setCampaignToPlanned($campaign_id);
        if ($has_changed) {
          $this->log("Campaign {$campaign_id} set to status 'planned'");
        } else {
          $this->log("Campaign {$campaign_id} was already in status 'planned'");
        }
        break;

      case 'restart':
      case 'restart_t':
        $this->setCampaignToPlanned($campaign_id); # otherwise we can't re-start it
        $segment_order = $this->getSegmentOrder($campaign_id);
        CRM_Segmentation_Logic::startCampaign($campaign_id, $segment_order);
        $this->log("Campaign {$campaign_id} has been consolidated and (re)started.");
        break;

      default:
      case 'leave':
        // do nothing
        break;
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

  /**
   * get the campaign status options
   */
  public static function getCampaignStatusOptions() {
    return array(
      'leave'     => E::ts("don't change status"),
      'planned'   => E::ts("(re)set to 'planned'"),
      // 'start'     => E::ts("start (if 'planned') with fixed segment order"),
      // 'start_t'   => E::ts("start (if 'planned') with segment order from table"),
      'restart'   => E::ts("(re)start with fixed segment order"),
      'restart_t' => E::ts("(re)start with segment order from table"),
      );
  }

  /**
   * make sure the status of the campaign is 'planned' (1)
   *
   * @return TRUE if the campaign needed to be modified for this
   */
  protected function setCampaignToPlanned($campaign_id) {
    $campaign = civicrm_api3('Campaign', 'getsingle', array(
      'id'     => $campaign_id,
      'return' => 'id,status_id'));
    if ($campaign['status_id'] == 1) {
      return FALSE;
    } else {
      civicrm_api3('Campaign', 'create', array(
        'id'        => $campaign_id,
        'status_id' => 1));
      return TRUE;
    }
  }

  /**
   * Extract a segment order from the configuration
   * The order will be based on the current order, with
   * the configured parts going to the top
   */
  protected function getSegmentOrder($campaign_id) {
    $new_order = array();
    $status_change = $this->getConfigValue('start');

    if ($status_change == 'restart_t') {
      // get the order from the table
      $table_name = $this->getConfigValue('segment_order_table');
      $this->resolveTableToken($table_name);
      $query = CRM_Core_DAO::executeQuery("SELECT DISTINCT(`segment_name`) AS sname FROM `{$table_name}` ORDER BY `segment_weight` ASC");
      while ($query->fetch()) {
        // look up segment by name
        $segment = civicrm_api3('Segmentation', 'getsegmentid', array('name' => $query->sname));
        if (!empty($segment['id'])) {
          $new_order[] = $segment['id'];
        } else {
          $this->log("Warning: Couldn't resolve segment '{$query->sname}'.");
        }
      }

    } else {
      // get the order from the text field
      $order_value = $this->getConfigValue('segment_order');
      $segment_names = explode("\n", $order_value);
      foreach ($segment_names as $segment_name) {
        $segment_name = trim($segment_name);
        $segment_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM `civicrm_segmentation_index` WHERE name = %1", array(1 => array($segment_name, 'String')));
        if ($segment_id) {
          $new_order[] = $segment_id;
        } else {
          $this->log("Warning: referenced segment '{$segment_name}' could not be identified!");
        }
      }
    }

    // extend the remaining according to the current order
    $current_order = CRM_Segmentation_Logic::getSegmentOrder($campaign_id, TRUE);
    foreach ($current_order as $segment_id) {
      if ($segment_id && !in_array($segment_id, $new_order)) {
        $new_order[] = $segment_id;
      }
    }

    return $new_order;
  }

  public static function isSupported() {
    return CRM_Sqltasks_Utils::isSegmentationInstalled();
  }

}
