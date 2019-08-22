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
 * this page runs a single task and displays the results
 */
class CRM_Sqltasks_Page_Runner extends CRM_Core_Page {

  public function run() {
    // get the task ID
    $task_id = CRM_Utils_Request::retrieve('tid', 'Integer');
    $reload  = CRM_Utils_Request::retrieve('reload', 'Integer');
    $input_val  = CRM_Utils_Request::retrieve('input_val', 'String');
    $input_val_urlencoded  =  urlencode($input_val);

    if (!is_numeric($task_id)) {
      throw new Exception("Invalid task id (tid) given.", 1);
    } elseif ($task_id) {
      $task = CRM_Sqltasks_Task::getTask($task_id);
    } else {
      throw new Exception("Invalid task id (tid) given.", 1);
    }

    // set title
    CRM_Utils_System::setTitle(E::ts("Running SQL task '%1'", array(1 => $task->getAttribute('name'))));

    $this->assign('task_id',    $task_id);
    $this->assign('reload',     $reload);
    $this->assign('input_val', $input_val);
    $this->assign('input_val_urlencoded', $input_val_urlencoded);
    $this->assign('reload_url', CRM_Utils_System::url('civicrm/sqltasks/run', "reload=1&tid={$task_id}&input_val={$input_val_urlencoded}"));

    parent::run();
  }

}
