<?php
/*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2019 SYSTOPIA                            |
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
 * Class CRM_Sqltasks_Page_Mytasks
 *
 * This page simply renders a list of task that may be executed by the current user
 */
class CRM_Sqltasks_Page_Mytasks extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts("Available SQL Tasks"));

    $allowed_tasks = [];

    foreach (CRM_Sqltasks_BAO_SqlTask::generator() as $task) {
      if (!$task->allowedToRun() || !is_null($task->archive_date)) continue;

      $allowed_tasks[$task->id] = [
        'id'              => $task->id,
        'name'            => $task->name,
        'last_runtime'    => sprintf("%.3f", ($task->last_runtime / 1000.0)),
        'description'     => $task->description,
        'input_required'  => $task->input_required,
      ];
    }

    $this->assign('tasks', $allowed_tasks);

    CRM_Core_Resources::singleton()->addStyleFile('de.systopia.sqltasks', 'css/sqltasks.css');

    parent::run();
  }
}
