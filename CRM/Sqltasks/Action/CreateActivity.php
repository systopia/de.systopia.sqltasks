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
class CRM_Sqltasks_Action_CreateActivity extends CRM_Sqltasks_Action_ContactSet {

  /**
   * Get identifier string
   */
  public function getID() {
    return 'activity';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('Create Activity');
  }

  /**
   * Build the configuration UI
   */
  public function buildForm(&$form) {
    parent::buildForm($form);

    $form->add(
      'checkbox',
      $this->getID() . '_use_api',
      E::ts('Use API (slow)')
    );

    $form->add(
      'checkbox',
      $this->getID() . '_individual',
      E::ts('Individual Activities')
    );

    $form->add(
      'select',
      $this->getID() . '_activity_type_id',
      E::ts('Activity Type'),
      $this->getOptions('activity_type')
    );

    $form->add(
      'select',
      $this->getID() . '_status_id',
      E::ts('Status'),
      $this->getOptions('activity_status')
    );

    $form->add(
      'text',
      $this->getID() . '_subject',
      E::ts('Subject'),
      array('class' => 'huge')
    );

    $form->add(
      'textarea',
      $this->getID() . '_details',
      E::ts('Details'),
      array('rows' => 4, 'cols' => 60),
      FALSE
    );

    $form->add(
      'text',
      $this->getID() . '_activity_date_time',
      E::ts('Timestamp'),
      FALSE
    );

    $form->add(
      'select',
      $this->getID() . '_campaign_id',
      E::ts('Campaign'),
      $this->getEligibleCampaigns(TRUE),
      FALSE
    );

    $form->add(
      'text',
      $this->getID() . '_source_contact_id',
      E::ts('Source Contact')
    );

    $form->add(
      'text',
      $this->getID() . '_assigned_to',
      E::ts('Assigned To')
    );
  }


  /**
   * RUN this action
   */
  public function execute() {
    $this->resetHasExecuted();
    $individual = $this->getConfigValue('individual');
    if ($individual) {
      $this->createIndividualActivities();
    } else {
      $this->createMassActivity();
    }
  }

  /**
   * Generate individual activities
   */
  public function createMassActivity() {
    $use_api       = $this->getConfigValue('use_api');
    $contact_table = $this->getContactTable();

    // load one line for the tokens
    $record = CRM_Core_DAO::executeQuery("SELECT * FROM {$contact_table} LIMIT 1;");
    $entries_exist = $record->fetch();
    if ($entries_exist) {
      $this->setHasExecuted();
    }

    // create activity first
    $activity_data = array(
      'activity_date_time' => $this->getDateTime($this->getConfigValue('activity_date_time')),
      'activity_type_id'   => $this->getConfigValue('activity_type_id'),
      'campaign_id'        => $this->getConfigValue('campaign_id'),
      'status_id'          => $this->getConfigValue('status_id'),
      'source_contact_id'  => $this->getConfigValue('source_contact_id'),
      'subject'            => $this->resolveTokens($this->getConfigValue('subject'), $record),
      'details'            => $this->resolveTokens($this->getConfigValue('details'), $record),
      'assignee_id'        => $this->getIDList($this->getConfigValue('assigned_to')));
    if (empty($activity_data['source_contact_id'])) {
      unset($activity_data['source_contact_id']);
    }
    if (empty($activity_data['campaign_id'])) {
      unset($activity_data['campaign_id']);
    }
    $activity = civicrm_api3('Activity', 'create', $activity_data);

    if ($use_api) {
      // add all targets separately
      $target_query = CRM_Core_DAO::executeQuery("SELECT contact_id FROM `{$contact_table}` WHERE contact_id IS NOT NULL;");
      while ($target_query->fetch()) {
        civicrm_api3('ActivityContact', 'create', array(
          'activity_id'    => $activity['id'],
          'contact_id'     => (int) $target_query->contact_id,
          'record_type_id' => 3));
      }

    } else {
      // just add everyone in the group as a target
      CRM_Core_DAO::executeQuery("
        INSERT IGNORE INTO civicrm_activity_contact
         (SELECT
            NULL              AS id,
            {$activity['id']} AS activity_id,
            contact_id        AS contact_id,
            3                 AS record_type
          FROM `{$contact_table}`
          WHERE contact_id IS NOT NULL);");

      if (class_exists('CRM_Segmentation_Logic')) {
        CRM_Segmentation_Logic::addSegmentForMassActivity($activity['id'], $this->getConfigValue('campaign_id'));
      }
    }
  }


  /**
   * Generate individual activities
   */
  public function createIndividualActivities() {
    $use_api       = $this->getConfigValue('use_api');
    $contact_table = $this->getContactTable();

    // static activity parameters
    $activity_template = array(
      'activity_date_time' => $this->getDateTime($this->getConfigValue('activity_date_time')),
      'activity_type_id'   => $this->getConfigValue('activity_type_id'),
      'campaign_id'        => $this->getConfigValue('campaign_id'),
      'status_id'          => $this->getConfigValue('status_id'),
      'source_contact_id'  => $this->getConfigValue('source_contact_id'),
      'assignee_id'        => $this->getIDList($this->getConfigValue('assigned_to')));
    if (empty($activity_template['source_contact_id'])) {
      unset($activity_template['source_contact_id']);
    }
    if (empty($activity_template['campaign_id'])) {
      unset($activity_template['campaign_id']);
    }
    if (!$use_api) {
      // add some defaults for SQL
      $activity_template['priority_id'] = 2;
      $activity_template['is_test'] = 0;
      $activity_template['is_auto'] = 0;
      $activity_template['is_current_revision'] = 1;
      $activity_template['is_deleted'] = 0;
    }

    // now iterate through all entries
    $record = CRM_Core_DAO::executeQuery("SELECT * FROM {$contact_table};");
    while ($record->fetch()) {
      if (empty($record->contact_id)) continue;
      $this->setHasExecuted();

      // compile activity
      $activity = $activity_template;
      $activity['subject']   = $this->resolveTokens($this->getConfigValue('subject'), $record);
      $activity['details']   = $this->resolveTokens($this->getConfigValue('details'), $record);
      $activity['target_id'] = (int) $record->contact_id;

      if ($use_api) {
        civicrm_api3('Activity', 'create', $activity);

      } else {
        $this->createActivitySQL($activity);
      }
    }
  }

  /**
   * use SQL to create that activity
   */
  protected function createActivitySQL($data) {
    // use the BAO
    $activity = new CRM_Activity_BAO_Activity();
    foreach ($data as $key => $value) {
      $activity->$key = $value;
    }
    $activity = $activity->save();

    if (!empty($data['target_id'])) {
      $link = new CRM_Activity_BAO_ActivityContact();
      $link->contact_id     = (int) $data['target_id'];
      $link->activity_id    = (int) $activity->id;
      $link->record_type_id = 3;
      $link->save();
      if (class_exists('CRM_Segmentation_Logic')) {
        CRM_Segmentation_Logic::addSegmentForActivityContact(
          $link->activity_id, $link->contact_id
        );
      }
      $link->free();
    }

    if (!empty($data['source_contact_id'])) {
      $link = new CRM_Activity_BAO_ActivityContact();
      $link->contact_id     = (int) $data['source_contact_id'];
      $link->activity_id    = (int) $activity->id;
      $link->record_type_id = 2;
      $link->save();
      $link->free();
    }

    if (!empty($data['assignee_id']) && is_array($data['assignee_id'])) {
      foreach ($data['assignee_id'] as $contact_id) {
        $link = new CRM_Activity_BAO_ActivityContact();
        $link->contact_id     = (int) $contact_id;
        $link->activity_id    = (int) $activity->id;
        $link->record_type_id = 1;
        $link->save();
        $link->free();
      }
    }

    $activity->free();
  }

  /**
   * Extract and format the time
   */
  protected function getDateTime($string) {
    if (empty($string)) {
      $string = 'now';
    }

    return date('YmdHis', strtotime($string));
  }
}