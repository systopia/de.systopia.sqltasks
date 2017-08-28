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
class CRM_Sqltasks_Task {

  protected static $main_attributes = array(
    'name'            => 'String',
    'description'     => 'String',
    'scheduled'       => 'String',
    'enabled'         => 'Integer',
    'weight'          => 'Integer',
    'last_execution'  => 'Date',
    'main_sql'        => 'String',
    'post_sql'        => 'String');

  protected $task_id;
  protected $attributes;
  protected $config;
  protected $log_messages;

  /**
   * Constructor
   */
  public function __construct($task_id, $data = array()) {
    $this->task_id      = $task_id;
    $this->attributes   = array();
    $this->config       = array();
    $this->log_messages = array();

    // main attributes go into $this->attributes
    foreach (self::$main_attributes as $attribute_name => $attribute_type) {
      $this->attributes[$attribute_name] = CRM_Utils_Array::value($attribute_name, $data);
    }

    // everything else goes into $this->config
    foreach ($data as $attribute_name => $value) {
      if (!isset(self::$main_attributes[$attribute_name])) {
        $this->config[$attribute_name] = $value;
      }
    }
  }

  /**
   * get a single attribute from the task
   */
  public function getID() {
    return $this->task_id;
  }

  /**
   * get configuration
   */
  public function getConfiguration() {
    return $this->config;
  }

  /**
   * append log messages
   */
  public function log($message) {
    $this->log_messages[] = $message;
  }

  /**
   * clear log
   */
  public function resetLog() {
    $this->log_messages = array();
  }
  /**
   * get a single attribute from the task
   */
  public function getAttribute($attribute_name) {
    return CRM_Utils_Array::value($attribute_name, $this->attributes);
  }

  /**
   * set a single attribute
   */
  public function setAttribute($attribute_name, $value, $writeTrough = FALSE) {
    if (isset(self::$main_attributes[$attribute_name])) {
      $this->attributes[$attribute_name] = $value;
      if ($writeTrough && $this->task_id) {
        CRM_Core_DAO::executeQuery("UPDATE `civicrm_sqltasks`
                                    SET `{$attribute_name}` = %1
                                    WHERE id = {$this->task_id}",
                                    array(1 => array($value, self::$main_attributes[$attribute_name])));
      }
    } else {
      throw new Exception("Attribute '{$attribute_name}' unknown", 1);
    }
  }

  /**
   * Store this task (create or update)
   */
  public function store() {
    // sort out paramters
    $params = array();
    $fields = array();
    $index  = 1;
    foreach (self::$main_attributes as $attribute_name => $attribute_type) {
      $value = $this->getAttribute($attribute_name);
      if ($value === NULL || $value === '') {
        $fields[$attribute_name] = "NULL";
      } else {
        $fields[$attribute_name] = "%{$index}";
        $params[$index] = array($value, $attribute_type);
        $index += 1;
      }
    }
    $fields['config'] = "%{$index}";
    $params[$index] = array(json_encode($this->config), 'String');

    // generate SQL
    if ($this->task_id) {
      $field_assignments = array();
      foreach ($fields as $key => $value) {
        $field_assignments[] = "`{$key}` = {$value}";
      }
      $field_assignment_sql = implode(', ', $field_assignments);
      $sql = "UPDATE `civicrm_sqltasks` SET {$field_assignment_sql} WHERE id = {$this->task_id}";
    } else {
      $columns = array();
      $values  = array();
      foreach ($fields as $key => $value) {
        $columns[] = $key;
        $values[]  = $value;
      }
      $columns_sql = implode(',', $columns);
      $values_sql  = implode(',', $values);
      $sql = "INSERT INTO `civicrm_sqltasks` ({$columns_sql}) VALUES ({$values_sql});";
    }
    // error_log("STORE QUERY: " . $sql);
    // error_log("STORE PARAM: " . json_encode($params));
    CRM_Core_DAO::executeQuery($sql, $params);
  }



  /**
   * Executes the given task
   */
  public function execute() {
    $this->resetLog();

    // 1. run the main SQL
    $this->executeSQLScript($this->getAttribute('main_sql'), "Main SQL");

    // 2. run the actions
    $actions = CRM_Sqltasks_Action::getAllActiveActions($this);
    foreach ($actions as $action) {
      $action_name = $action->getName();
      $timestamp = microtime(TRUE);
      try {
        $action->execute();
        $runtime = sprintf("%.3f", (microtime(TRUE) - $timestamp));
        $this->log("Action '{$action_name}' executed in {$runtime}s.");
      } catch (Exception $e) {
        $this->log("Error in action '{$action_name}': " . $e -> getMessage());
      }
    }

    // 3. run the post SQL
    $this->executeSQLScript($this->getAttribute('post_sql'), "Post SQL");

    return $this->log_messages;
  }

  /**
   * execute a single SQL script
   */
  protected function executeSQLScript($script, $script_name) {
    if (empty($script)) {
      $this->log("No '{$script_name}'given.");
      return;
    }

    $timestamp = microtime(TRUE);
    try {
      $script = html_entity_decode($script);
      error_log($script);
      CRM_Core_DAO::executeQuery($script);
      $runtime = sprintf("%.3f", (microtime(TRUE) - $timestamp));
      $this->log("Script '{$script_name}' executed in {$runtime}s.");
    } catch (Exception $e) {
      $this->log("Script '{$script_name}' failed: " . $e -> getMessage());
    }
  }

  /**
   * delete a task with the given ID
   */
  public static function delete($tid) {
    $tid = (int) $tid;
    if (empty($tid)) return NULL;
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_sqltasks WHERE id = {$tid}");
  }

  /**
   * Get a list of all tasks
   */
  public static function getAllTasks() {
    return self::getTasks('SELECT * FROM civicrm_sqltasks ORDER BY weight ASC');
  }

  /**
   * Get a list of tasks ready for execution
   */
  public static function getExecutionTaskList() {
    return self::getTasks('SELECT * FROM civicrm_sqltasks WHERE enabled=1 ORDER BY weight ASC');
  }

  /**
   * Load a list of tasks based on the data yielded by the given SQL query
   */
  public static function getTasks($sql_query) {
    $tasks = array();
    $task_search = CRM_Core_DAO::executeQuery($sql_query);
    while ($task_search->fetch()) {
      $data = array();
      foreach (self::$main_attributes as $attribute_name => $attribute_type) {
        $data[$attribute_name] = $task_search->$attribute_name;
      }
      if (isset($task_search->config)) {
        $config = json_decode($task_search->config, TRUE);
        foreach ($config as $key => $value) {
          $data[$key] = $value;
        }
      }
      $tasks[] = new CRM_Sqltasks_Task($task_search->id, $data);
    }

    return $tasks;
  }

  /**
   * Load a list of tasks based on the data yielded by the given SQL query
   */
  public static function getTask($tid) {
    $tid = (int) $tid;
    if (empty($tid)) return NULL;
    $tasks = self::getTasks("SELECT * FROM `civicrm_sqltasks` WHERE id = {$tid}");
    return reset($tasks);
  }
}