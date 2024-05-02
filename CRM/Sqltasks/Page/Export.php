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
    $task_id = CRM_Utils_Request::retrieve('id', 'Integer');

    if ($task_id) {
      $task = CRM_Sqltasks_BAO_SqlTask::findById($task_id);

      $task_data = $task->exportData([
        'abort_on_error',
        'config',
        'category',
        'description',
        'input_required',
        'last_modified',
        'parallel_exec',
        'run_permissions',
        'scheduled',
      ]);

      $file_name = preg_replace('/[^A-Za-z0-9_\- ]/', '', $task->name) . '.sqltask';
      $file_content = json_encode($task_data, JSON_PRETTY_PRINT);

      CRM_Utils_System::download($file_name, 'application/json', $file_content);
    }
  }

}
