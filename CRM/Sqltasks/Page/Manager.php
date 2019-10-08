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
 * The "Task Manager" lets you control the various
 * scheduled jobs
 */
class CRM_Sqltasks_Page_Manager extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('SQL Task Manager'));

    // first: process commands (if any)
    $this->processExportCommand();
    $this->processDeleteCommand();
    $this->processEnableDisableCommand();
    $this->processRearrangeCommand();

    // render tasks
    $tasks = CRM_Sqltasks_Task::getAllTasks();
    $rendered_tasks = array();
    foreach ($tasks as $task) {
      $rendered_tasks[] = $task->getPreparedTask();
    }
    $this->assign('tasks', $rendered_tasks);
    $this->assign('baseurl', CRM_Utils_System::url('civicrm/sqltasks/manage'));

    // add scheduler information
    $config = CRM_Sqltasks_Config::singleton();
    $frequency = $config->getCurrentDispatcherFrequency();
    $this->assign('dispatcher_frequency', $frequency);

    /*
    * Add sortTasks.js to the view
    */
    CRM_Core_Resources::singleton()->addScriptFile('de.systopia.sqltasks', 'js/sortTasks.js');

    parent::run();
  }

  /**
   * export a file
   */
  protected function processExportCommand() {
    $export_id = CRM_Utils_Request::retrieve('export', 'Integer');
    if ($export_id) {
      $task = CRM_Sqltasks_Task::getTask($export_id);
      $config = $task->exportConfiguration();
      CRM_Utils_System::download(
        preg_replace('/[^A-Za-z0-9_\- ]/', '', $task->getAttribute('name')) . '.sqltask',
        'application/json',
        $config);
    }
  }



  /**
   * Process the 'enable' and 'disable' command
   */
  protected function processEnableDisableCommand() {
    $enable_id  = CRM_Utils_Request::retrieve('enable', 'Integer');
    $disable_id = CRM_Utils_Request::retrieve('disable', 'Integer');

    if ($enable_id) {
      $task = CRM_Sqltasks_Task::getTask($enable_id);
      $task->setAttribute('enabled', 1);
      $task->store();
    }

    if ($disable_id) {
      $task = CRM_Sqltasks_Task::getTask($disable_id);
      $task->setAttribute('enabled', 0);
      $task->store();
    }
  }

  /**
   * Delete task
   */
  protected function processDeleteCommand() {
    $delete_id = CRM_Utils_Request::retrieve('delete', 'Integer');
    $confirmed = CRM_Utils_Request::retrieve('confirmed', 'Integer');
    if ($delete_id) {
      if ($confirmed) {
        CRM_Sqltasks_Task::delete($delete_id);
      } else {
        $task = CRM_Sqltasks_Task::getTask($delete_id);
        $this->assign('delete', $task->getPreparedTask());
      }
    }
  }

  /**
   * Process the order rearrangement commands
   */
  protected function processRearrangeCommand() {
    foreach (array('top', 'up', 'down', 'bottom') as $cmd) {
      $task_id = CRM_Utils_Request::retrieve($cmd, 'Integer');
      if (!$task_id) continue;

      $task_order = CRM_Sqltasks_Task::getAllTasks();
      $original_task_order = $task_order;

      // find the task
      $index = FALSE;
      for ($i=0; $i < count($task_order); $i++) {
        if ($task_order[$i]->getID() == $task_id) {
          $index = $i;
          break;
        }
      }

      if ($index !== FALSE) {
        switch ($cmd) {
          case 'top':
            $new_index = 0;
            break;
          case 'up':
            $new_index = max(0, $index-1);
            break;
          case 'down':
            $new_index = min(count($task_order)-1, $index+1);
            break;
          default:
          case 'bottom':
            $new_index = count($task_order)-1;
            break;
        }
        // copied from https://stackoverflow.com/questions/12624153/move-an-array-element-to-a-new-index-in-php
        $out = array_splice($task_order, $index, 1);
        array_splice($task_order, $new_index, 0, $out);
      }

      // store the new task order
      if ($task_order != $original_task_order) {
        $weight = 10;
        foreach ($task_order as $task) {
          $task->setAttribute('weight', $weight, TRUE);
          $weight += 10;
        }
      }
    }
  }
}
