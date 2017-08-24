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
class CRM_Sqltasks_Form_Configure extends CRM_Core_Form {

  public function buildQuickForm() {

    // compile the form
    $this->add(
      'text',
      'name',
      E::ts('Name'),
      array('class' => 'huge'),
      TRUE
    );

    $this->add(
      'text',
      'description',
      E::ts('Description'),
      array('class' => 'huge'),
      FALSE
    );

    $this->add(
      'textarea',
      'pre_sql',
      E::ts('Pre-SQL'),
      array('rows' => 8,
            'cols' => 60,
      ),
      FALSE
    );

    $this->add(
      'textarea',
      'select_sql',
      E::ts('Select-SQL'),
      array('rows' => 8,
            'cols' => 60,
      ),
      FALSE
    );

    $this->add(
      'textarea',
      'post_sql',
      E::ts('Post-SQL'),
      array('rows' => 8,
            'cols' => 60,
      ),
      FALSE
    );


    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();


    CRM_Core_Session::setStatus(E::ts('You picked color "%1"', array(
      1 => $options[$values['favorite_color']],
    )));
    parent::postProcess();
  }
}
