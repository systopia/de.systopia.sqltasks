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

  /**
   * build FORM
   */
  public function buildQuickForm() {

    // get the ID
    $task_id = CRM_Utils_Request::retrieve('tid', 'Integer');
    if (!is_numeric($task_id)) {
      throw new Exception("Invalid task id (tid) given.", 1);
    }

    $this->task = CRM_Sqltasks_Task::getTask($task_id);
    if (!$this->task) {
      throw new Exception("Invalid task id (tid) given.", 1);
    }

    // set title
    CRM_Utils_System::setTitle(E::ts("Import '%1' Configuration", array(1 => $this->task->getAttribute('name'))));

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
    // update configuration
    $config_file = $_FILES['config_file'];
    $raw_data = file_get_contents($config_file['tmp_name']);
    $data = json_decode($raw_data, TRUE);
    if ($data) {
      $data = CRM_Sqltasks_Config_Format::toLatest($data);
      foreach ($data as $key => $value) {
        if ($key == 'config') {
          $this->task->setConfiguration($value);
        } else {
          $this->task->setAttribute($key, $value);
        }
      }
      $this->task->store();
      CRM_Core_Session::setStatus(E::ts('Configuration imported successfully.'), E::ts('Update Complete'));
    } else {
      CRM_Core_Session::setStatus(E::ts('Invalid config file.'), E::ts('Error'), 'error');
    }

    parent::postProcess();
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/sqltasks/manage'));
  }
}
