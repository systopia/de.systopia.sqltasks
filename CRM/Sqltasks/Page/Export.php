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
    else {
      $tasks = CRM_Sqltasks_Task::getAllTasks();
      if (!empty($tasks)) {
        $zip = new ZipArchive();
        $fileURL = CRM_Core_Config::singleton()->uploadDir . "sqltasks_" . date('Ymd') . ".zip";
        if ($zip->open($fileURL, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === TRUE) {
          foreach ($tasks as $task) {
            $taskFileName = preg_replace('/[^A-Za-z0-9_\- ]/', '', $task->getAttribute('name')) . '.sqltask';
            $zip->addFromString($taskFileName, $task->exportConfiguration());
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

}
