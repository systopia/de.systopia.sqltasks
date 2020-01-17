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
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Sqltasks_Form_ConfigImport extends CRM_Core_Form {

  /** @var CRM_Sqltasks_Task */
  protected $task = NULL;

  /**
   * build FORM
   */
  public function buildQuickForm() {
    // get the ID
    $task_id = CRM_Utils_Request::retrieve('tid', 'Integer');

    if ($task_id == 0) {
      $this->task = new CRM_Sqltasks_Task($task_id, ['name' => "NEW TASK"]);
      CRM_Utils_System::setTitle(E::ts("Import new SQL-Task from a file"));

    } else if (is_numeric($task_id)) {
      $this->task = CRM_Sqltasks_Task::getTask($task_id);
      if (!$this->task) {
        throw new Exception("Invalid task id (tid) given.", 1);
      }
      CRM_Utils_System::setTitle(E::ts("Import '%1' Configuration", array(1 => $this->task->getAttribute('name'))));

    } else {
      throw new Exception("Invalid task id (tid) given.", 1);
    }

    // add some hidden attributes
    $this->add('hidden', 'tid', $task_id);

    // add form elements
    $this->add(
      'file',
      'config_file',
      E::ts('Configuration file to be imported:'),
      TRUE
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Import'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }

  /**
   * process uploaded file
   */
  public function postProcess() {
    $values = $this->exportValues();

    // load data
    $config_file = $_FILES['config_file'];
    $raw_data = file_get_contents($config_file['tmp_name']);

    // set name for new tasks
    if (empty($values['tid'])) {
      $this->task->setAttribute('name', explode('.', $config_file['name'])[0]);
    }

    // parse data
    $data = json_decode($raw_data, TRUE);
    if ($data) {
      // OLD FILE FORMAT
      foreach ($data as $key => $value) {
        if ($key == 'config') {
          $this->task->setConfiguration($value);
        } else {
          $this->task->setAttribute($key, $value);
        }
      }
      $this->task->store();
      CRM_Core_Session::setStatus(E::ts('Configuration imported successfully.'), E::ts('Import Complete'), 'info');
      CRM_Core_Session::setStatus(E::ts('Always double-check an imported configuration before executing it!'), E::ts('Warning'), 'warn');

    } else {
      // check for the new file format:
      if (   substr($raw_data, 0, strlen(CRM_Sqltasks_Config::SQLTASK_FILE_FORMAT_FILE_HEADER)) == CRM_Sqltasks_Config::SQLTASK_FILE_FORMAT_FILE_HEADER
          && strstr($raw_data, CRM_Sqltasks_Config::SQLTASK_FILE_FORMAT_MAIN_HEADER)
          && strstr($raw_data, CRM_Sqltasks_Config::SQLTASK_FILE_FORMAT_POST_HEADER)) {

        // NEW FILE FORMAT
        $start_main = strpos($raw_data, CRM_Sqltasks_Config::SQLTASK_FILE_FORMAT_MAIN_HEADER);
        $start_post = strpos($raw_data, CRM_Sqltasks_Config::SQLTASK_FILE_FORMAT_POST_HEADER);
        $len_header = strlen(CRM_Sqltasks_Config::SQLTASK_FILE_FORMAT_FILE_HEADER);
        $len_main   = strlen(CRM_Sqltasks_Config::SQLTASK_FILE_FORMAT_MAIN_HEADER);
        $len_post   = strlen(CRM_Sqltasks_Config::SQLTASK_FILE_FORMAT_POST_HEADER);

        $config   = substr($raw_data, $len_header, ($start_main - $len_header));
        $main_sql = substr($raw_data, ($start_main + $len_main), ($start_post - $start_main - $len_main));
        $post_sql = substr($raw_data, ($start_post + $len_post));

        $data = json_decode($config, TRUE);
        if (!$data) {
          CRM_Core_Session::setStatus(E::ts('Bad config data.'), E::ts('Error'), 'error');
        } else {
          foreach ($data as $key => $value) {
            if ($key == 'config') {
              $this->task->setConfiguration($value);
            } else {
              $this->task->setAttribute($key, $value);
            }
          }
          $this->task->setAttribute('main_sql', $main_sql);
          $this->task->setAttribute('post_sql', $post_sql);
          $this->task->store();
          CRM_Core_Session::setStatus(E::ts('Configuration imported successfully.'), E::ts('Import Complete'), 'info');
          CRM_Core_Session::setStatus(E::ts('Always double-check an imported configuration before executing it!'), E::ts('Warning'), 'warn');
        }


      } else {
        // BAD FILE FORMAT
        CRM_Core_Session::setStatus(E::ts('Invalid config file.'), E::ts('Error'), 'error');
      }
    }

    parent::postProcess();
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/sqltasks/manage'));
  }
}
