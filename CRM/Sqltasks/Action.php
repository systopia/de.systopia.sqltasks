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
 * This class represents a single task
 *
 * @todo turn this into an entity
 */
abstract class CRM_Sqltasks_Action {

  protected $task = NULL;
  protected $config = NULL;

  public function __construct($task) {
    $this->task = $task;
    $this->config = $task->getConfiguration();
  }

  /**
   * Get identifier string
   */
  abstract public function getID();

  /**
   * Get a human readable name
   */
  abstract public function getName();

  /**
   * RUN this action
   */
  abstract public function execute();

  /**
   * log to the task (during execution)
   */
  public function log($message) {
    $this->task->log($message);
  }

  /**
   * Check if this action is currently enabled
   */
  public function isEnabled() {
    $key = $this->getID() . '_enabled';
    return !empty($this->config[$key]);
  }

  /**
   * Check if this action is configured correctly
   * Overwrite for checks
   */
  public function checkConfiguration() {
    // nothing to to
  }



  /**
   * Build the configuration UI
   */
  public function buildForm(&$form) {
    // add the 'enabled' element
    $form->add(
      'checkbox',
      $this->getID() . '_enabled',
      $this->getName(),
      '',
      FALSE,
      array('class' => 'crm-sqltask-action-enable')
    );
  }

  /**
   * get the template file for the configuration UI
   */
  public function getFormTemplate() {
    // default is: same path
    $class_name = get_class($this);
    $tpl_name = str_replace('_', '/', $class_name) . '.tpl';
    return $tpl_name;
  }

  /**
   * Get a list of all potential actions for this task
   * @todo find automatically?
   */
  public static function getAllActions($task) {
    // just compile list manually (for now)
    $actions[] = new CRM_Sqltasks_Action_SyncGroup($task);

    return $actions;
  }

  /**
   * Get a list of all active actions for this task,
   * ready for execution
   */
  public static function getAllActiveActions($task) {
    $actions = self::getAllActions($task);
    $active_actions = array();
    foreach ($actions as $action) {
      if ($action->isEnabled()) {
        $active_actions[] = $action;
      }
    }
    return $actions;
  }
}