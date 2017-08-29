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
      $this->getOptions('activity_type'),
      TRUE
    );

    $form->add(
      'select',
      $this->getID() . '_status_id',
      E::ts('Status'),
      $this->getOptions('activity_status'),
      TRUE
    );

    $form->add(
      'text',
      $this->getID() . '_subject',
      E::ts('Subject'),
      array('class' => 'huge'),
      TRUE
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
      $this->getEligibleCampaigns(),
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

    // create activity first
    $activity = array(
      'activity_date_time' => $this->getDateTime($this->getConfigValue('activity_date_time')),
      'activity_type_id'   => $this->getConfigValue('activity_type_id'),
      'campaign_id'        => $this->getConfigValue('campaign_id'),
      'status_id'          => $this->getConfigValue('status_id'),
      'source_contact_id'  => $this->getConfigValue('source_contact_id'),
      'subject'            => $this->getConfigValue('subject'),
      'details'            => $this->getConfigValue('details'),
      'assigned_to'        => $this->getIDList($this->getConfigValue('assigned_to')));
    civicrm_api3('Activity', 'create', $activity);

    if ($use_api) {
      // TODO: add all targets separately

    } else {
      // TODO: assign all targets by SQL

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
      'assigned_to'        => $this->getIDList($this->getConfigValue('assigned_to')));

    // now iterate through all entries
    $record = CRM_Core_DAO::executeQuery("SELECT * FROM {$contact_table};");
    while ($record->fetch()) {
      if (empty($record->contact_id)) continue;

      // compile activity
      $activity = $activity_template;
      $activity['subject']   = $this->resolveTokens($this->getConfigValue('subject'), $record);
      $activity['details']   = $this->resolveTokens($this->getConfigValue('details'), $record);
      $activity['target_id'] = (int) $record->contact_id;

      if ($use_api) {
        civicrm_api3('Activity', 'create', $activity);
      } else {
        $this->createActivtySQL($activity);
      }
    }
  }





  /**
   * get a list of eligible groups
   */
  protected function getEligibleCampaigns() {
    $campaign_list = array();
    $campaign_query = civicrm_api3('Campaign', 'get', array(
      'is_enabled'   => 1,
      'option.limit' => 0,
      'return'       => 'id,title'))['values'];
    foreach ($campaign_query as $campaign) {
      $campaign_list[$campaign['id']] = CRM_Utils_Array::value('title', $campaign, "Campaign {$campaign['id']}");
    }
    return $campaign_list;
  }
}