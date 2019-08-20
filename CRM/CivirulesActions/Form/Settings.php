<?php

use CRM_Sqltasks_ExtensionUtil as E;

class CRM_CivirulesActions_Form_Settings extends CRM_Core_Form {

  /**
   * Overridden parent method to build the form
   *
   * @access public
   */
  public function buildQuickForm() {
    $tableExists = CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE 'civirule_action';");

    if ($tableExists) {
      $addSqlTaskExists = CRM_Core_DAO::singleValueQuery(
        "SELECT COUNT(id) FROM `civirule_action` WHERE name = 'add_sql_task'"
      );

      if ($addSqlTaskExists) {
        $this->assign('message', E::ts('You have already had "Add SQL Task" in database'));
      }
      else {
        $this->assign('message', E::ts('Please click "Save" button to add "Add SQL Task" to database'));

        $saveButton = [
          [
            'type' => 'next',
            'name' => ts('Save'),
            'isDefault' => TRUE,
          ],
        ];
      }
    }
    else {
      $this->assign('message', E::ts('You do not have an extension installed (CiviRules)'));
    }

    $button = [
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ];

    if (!empty($saveButton)) {
      $button = array_merge($saveButton, $button);
    }

    $this->addButtons($button);

    parent::buildQuickForm();
  }

  /**
   * Overridden parent method to deal with processing after succesfull submit
   *
   * @access public
   */
  public function postProcess() {
    CRM_Core_DAO::executeQuery(
      "INSERT INTO civirule_action (name, label, class_name, is_active)
        VALUES('add_sql_task', 'Add SQL Task', 'CRM_CivirulesActions_SQLTask', 1)"
    );
  }
}
