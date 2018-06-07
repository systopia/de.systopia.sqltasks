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

  protected static $_campaign_list = NULL;

  /** @var CRM_Sqltasks_Task */
  protected $task = NULL;
  protected $config = NULL;
  protected $has_executed = TRUE;
  protected $context = [];

  /**
   * CRM_Sqltasks_Action constructor.
   *
   * @param $task CRM_Sqltasks_Task task
   */
  public function __construct($task) {
    $this->task = $task;
    $this->config = $task->getConfiguration();
    $this->has_executed = TRUE;
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
   * Get the given key from the config
   */
  public function getConfigValue($name, $prefix = 'ID') {
    if ($prefix == 'ID') {
      $prefix = $this->getID() . '_';
    }

    $key = $prefix . $name;
    if (isset($this->config[$key])) {
      return $this->config[$key];
    } else {
      return NULL;
    }
  }

  /**
   * Get a list of ints from the string
   */
  protected function getIDList($string) {
    $id_list = array();
    if (!empty($string)) {
      $items = explode(',', $string);
      foreach ($items as $item) {
        $value = (int) $item;
        if ($value) {
          $id_list[] = $value;
        }
      }
    }

    return $id_list;
  }

  /**
   * Replace all tokens in the string with data from the record
   */
  protected function resolveTokens($string, $record) {
    while (preg_match('/\{(?P<token>\w+)\}/', $string, $match)) {
      $token = $match['token'];
      $value = isset($record->$token) ? $record->$token : '';
      $string = str_replace('{' . $match['token'] . '}', $value, $string);
    }
    return $string;
  }

  /**
   * Check if this action is currently enabled
   */
  public function isEnabled() {
    $enabled = $this->getConfigValue('enabled');
    return !empty($enabled);
  }

  /**
   * Check if this action is a handler,
   * which has be be executed after all other actions
   */
  public function isResultHandler() {
    return FALSE;
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
   * get a list of the options from the given option group
   */
  protected function getOptions($option_group_name, $empty_option = TRUE) {
    $options = array();
    if ($empty_option) {
      $options[''] = E::ts('- none -');
    }
    $values = civicrm_api3('OptionValue', 'get', array(
      'option_group_id' => $option_group_name,
      'is_active'       => 1,
      'return'          => 'value,label',
      'option.limit'    => 0))['values'];
    foreach ($values as $option_value) {
      $options[$option_value['value']] = $option_value['label'];
    }

    return $options;
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

    // add Segmentation Extension tasks (de.systopia.segmentation)
    if (CRM_Sqltasks_Utils::isSegmentationInstalled()) {
      // this should run before CreateActivity and APICall because those tasks
      // might depend on segmentation data being available
      $actions[] = new CRM_Sqltasks_Action_SegmentationAssign($task);
    }
    $actions[] = new CRM_Sqltasks_Action_CreateActivity($task);
    $actions[] = new CRM_Sqltasks_Action_APICall($task);
    $actions[] = new CRM_Sqltasks_Action_CSVExport($task);
    $actions[] = new CRM_Sqltasks_Action_SyncTag($task);
    $actions[] = new CRM_Sqltasks_Action_SyncGroup($task);

    if (CRM_Sqltasks_Utils::isSegmentationInstalled()) {
      $actions[] = new CRM_Sqltasks_Action_SegmentationExport($task);
    }
    $actions[] = new CRM_Sqltasks_Action_CallTask($task);
    $actions[] = new CRM_Sqltasks_Action_ResultHandler($task, 'success', E::ts('Success Handler'));
    $actions[] = new CRM_Sqltasks_Action_ResultHandler($task, 'error',   E::ts('Error Handler'));
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
    return $active_actions;
  }

  /**
   * get a list of eligible groups
   */
  protected function getEligibleCampaigns($empty_option = FALSE) {
    $campaign_list = array();

    // add empty option (if requested)
    if ($empty_option) {
      $campaign_list[0] = E::ts('- none -');
    }

    // load campaigns (cached)
    if (self::$_campaign_list === NULL) {
      self::$_campaign_list = array();
      $campaign_query = civicrm_api3('Campaign', 'get', array(
        'is_active'    => 1,
        'option.limit' => 0,
        'option.sort'  => 'title ASC',
        'return'       => 'id,title'))['values'];
      foreach ($campaign_query as $campaign) {
        self::$_campaign_list[$campaign['id']] = CRM_Utils_Array::value('title', $campaign, "Campaign {$campaign['id']}");
      }
    }

    // add camapaigns to list
    foreach (self::$_campaign_list as $key => $value) {
      $campaign_list[$key] = $value;
    }

    return $campaign_list;
  }

  /**
   * If this action wants to use the
   *  has_executed FLAG (used for success handler)
   *  then it needs to first reset the FLAG
   *  and then use setHasExecuted to mark it
   */
  protected function resetHasExecuted() {
    $this->has_executed = FALSE;
  }

  /**
   * Mark that this action has executed,
   *  as opposed to 'done nothing'
   * @see ::resetHasExecuted
   */
  protected function setHasExecuted() {
    $this->has_executed = TRUE;
  }

  protected function _columnExists($table, $column) {
    return CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$table}` LIKE '{$column}';");
  }

  /**
   * check if this process has "done anything"
   */
  public function hasExecuted() {
    return $this->has_executed;
  }

  /**
   * Set execution context
   *
   * @param array $context
   */
  public function setContext(array $context) {
    $this->context = $context;
  }

  /**
   * Replace all tokens in the string with data from the context
   *
   * @param $string
   */
  public function resolveTableToken(&$string) {
    $string = str_replace('{random}', $this->context['random'], $string);
  }

}
