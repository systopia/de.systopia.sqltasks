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

class CRM_Sqltasks_Page_Export extends CRM_Core_Page {

  /**
   * Export task configuration to a file
   *
   * @return void|null
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $taskId = CRM_Utils_Request::retrieve('id', 'Integer');
    if ($taskId) {
      $task = CRM_Sqltasks_Task::getTask($taskId);
      $config = $task->exportConfiguration();
      CRM_Utils_System::download(
        preg_replace('/[^A-Za-z0-9_\- ]/', '', $task->getAttribute('name')) . '.sqltask',
        'application/json',
        $config);
    }
  }

}
