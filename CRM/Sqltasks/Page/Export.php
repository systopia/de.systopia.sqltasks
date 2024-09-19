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
      $file_name = $this->getTaskFileName($task);
      $file_content = $this->getTaskFileContent($task);

      CRM_Utils_System::download($file_name, 'application/json', $file_content);
    }
    else {
      $tasks = CRM_Sqltasks_BAO_SqlTask::generator();
      if ($tasks->valid()) {
        $zip = new ZipArchive();
        $fileURL = CRM_Core_Config::singleton()->uploadDir . "sqltasks_" . date('Ymd') . ".zip";
        if ($zip->open($fileURL, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === TRUE) {
          foreach ($tasks as $task) {
            $taskFileName = $this->getTaskFileName($task);
            $zip->addFromString($taskFileName, $this->getTaskFileContent($task));
          }
          $zip->close();
          $null = NULL;
          CRM_Utils_System::download(
            CRM_Utils_File::cleanFileName(basename($fileURL)),
            'application/zip',
            $null,
            NULL,
            FALSE
          );
          readfile($fileURL);
          unlink($fileURL);
          CRM_Utils_System::civiExit();
        }
        else {
          CRM_Core_Session::setStatus(E::ts('Cannot create ZIP file'), E::ts('Error'), 'error');
        }
      }
      else {
        CRM_Core_Session::setStatus(E::ts('There are no Tasks to be exported'), E::ts('Error'), 'error');
      }
    }
  }

  private function getTaskFileContent(CRM_Sqltasks_BAO_SqlTask $task) {
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
    return json_encode($task_data, JSON_PRETTY_PRINT);
  }

  private function getTaskFileName(CRM_Sqltasks_BAO_SqlTask $task) {
    return preg_replace('/[^A-Za-z0-9_\- ]/', '', $task->name) . '.sqltask';
  }

}
