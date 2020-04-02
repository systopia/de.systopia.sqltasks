<?php
/*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2018 SYSTOPIA                            |
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
 * This action will "simply" call another task
 *
 */
class CRM_Sqltasks_Action_CallTask extends CRM_Sqltasks_Action {

  /**
   * Get identifier string
   */
  public function getID() {
    return 'task';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('Run SQL Task(s)');
  }

  /**
   * Get default template order
   *
   * @return int
   */
  public function getDefaultOrder() {
    return 800;
  }

  /**
   * RUN this action
   */
  public function execute() {
    $this->resetHasExecuted();

    $tasks = $this->getConfigValue('tasks');
    $categories = $this->getConfigValue('categories');
    if (empty($tasks) && empty($categories)) {
      return;
    }

    // generate query for task selection
    $query = "SELECT * FROM `civicrm_sqltasks` WHERE enabled=1 AND ";
    $or_clauses = array();
    if (!empty($tasks)) {
      $or_clauses[] = '`id` IN (' . implode(',', $tasks) . ')';
    }
    if (!empty($categories)) {
      $escaped_categories = array();
      foreach ($categories as $category) {
        $escaped_categories[] = "'" . CRM_Core_DAO::escapeString($category) . "'";
      }
      $or_clauses[] = '`category` IN (' . implode(',', $escaped_categories) . ')';
    }
    $query .= '((' . implode(') OR (', $or_clauses). '))';
    $query .= ' ORDER BY weight ASC';
    $tasks2run = CRM_Sqltasks_Task::getTasks($query);

    foreach ($tasks2run as $task) {
      if ($task->getID() == $this->task->getID()) {
        continue;
      }
      // all good: execute!
      $logs = $task->execute();
      // log results from child task
      foreach ($logs as $log) {
        $this->log($log);
      }
      $this->log("Executed task '" . $task->getAttribute('name') . "' [" . $task->getID() . ']');
    }
  }

  /**
   * Get a list of all SQL Tasks
   */
  protected function getTaskList() {
    $task_options = array();
    $task_list = CRM_Sqltasks_Task::getExecutionTaskList();

    // make sure this one is not in it
    foreach ($task_list as $task) {
      $task_id = $task->getID();
      if ($task_id != $this->task->getID()) {
        $task_options[$task_id] = "[{$task_id}] " . $task->getAttribute('name');
      }
    }

    return $task_options;
  }

  /**
   * Get a list of all SQL Task categories
   */
  protected function getTaskCategoryList() {
    $categoryOptions = [];
    $categories = CRM_Sqltasks_Task::getTaskCategoryList();

    foreach ($categories as $category) {
      $categoryOptions[$category] = $category;
    }

    return $categoryOptions;
  }
}
