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

  /**
   * stores the task */
  protected $task = NULL;

  /**
   * Compile config form
   */
  public function buildQuickForm() {
    // get the ID
    $task_id = CRM_Utils_Request::retrieve('tid', 'Integer');
    if (!is_numeric($task_id)) {
      throw new Exception("Invalid task id (tid) given.", 1);
    }
    elseif ($task_id) {
      $this->task = CRM_Sqltasks_Task::getTask($task_id);
      CRM_Utils_System::setTitle(E::ts("Configure SQL Task '%1'", array(1 => $this->task->getAttribute('name'))));
    }
    else {
      $this->task = new CRM_Sqltasks_Task($task_id);
      CRM_Utils_System::setTitle(E::ts("Create new SQL Task"));
    }

    // add some hidden attributes
    $this->add('hidden', 'tid', $task_id);
    $this->add('hidden', 'enabled', $this->task->getAttribute('enabled'));
    $this->add('hidden', 'weight', $this->task->getAttribute('weight'));

    // BUILD MAIN FORM
    $this->add(
      'text',
      'name',
      E::ts('Name'),
      array('class' => 'huge', 'maxlength' => '64'),
      TRUE
    );

    $this->add(
      'textarea',
      'description',
      E::ts('Description'),
      array(
        'rows' => 8,
        'cols' => 60,
      ),
      FALSE
    );

    $this->add(
      'text',
      'category',
      E::ts('Category'),
      array('class' => 'huge', 'maxlength' => '64'),
      FALSE
    );

    $this->add(
      'textarea',
      'main_sql',
      E::ts('Main Script (SQL)'),
      array(
        'rows' => 8,
        'style' => 'font-family: monospace, monospace !important; width: 95%; min-width: 240px',
      ),
      FALSE
    );

    $this->add(
      'textarea',
      'post_sql',
      E::ts('Cleanup Script (SQL)'),
      array(
        'rows' => 8,
        'style' => 'font-family: monospace, monospace !important; width: 95%; min-width: 240px',
      ),
      FALSE
    );

    $this->add(
      'select',
      'scheduled',
      E::ts('Execution'),
      CRM_Sqltasks_Task::getSchedulingOptions(),
      TRUE
    );

    $weekdays = array(
      1 => E::ts("Monday"),
      2 => E::ts("Tuesday"),
      3 => E::ts("Wednesday"),
      4 => E::ts("Thursday"),
      5 => E::ts("Friday"),
      6 => E::ts("Saturday"),
      7 => E::ts("Sunday"),
    );
    $days = array();
    foreach (range(1, 31) as $day) {
      $days[$day] = $day;
    }
    foreach (range(1, 23) as $hour) {
      $hours[$hour] = str_pad($hour, 2, '0', STR_PAD_LEFT);
    }
    foreach (range(1, 59) as $minute) {
      $minutes[$minute] = str_pad($minute, 2, '0', STR_PAD_LEFT);
    }
    $this->add('select', 'scheduled_weekday', E::ts('Weekday'), $weekdays);
    $this->add('select', 'scheduled_day', E::ts('Day'), $days);
    $this->add('select', 'scheduled_hour', E::ts('Hour'), $hours);
    $this->add('select', 'scheduled_minute', E::ts('Minute'), $minutes);

    $this->add(
      'checkbox',
      'parallel_exec',
      E::ts('Allow parallel execution')
    );

    // BUILD ACTIONS
    $action_list = array();
    $actions = CRM_Sqltasks_Action::getAllActions($this->task);
    foreach ($actions as $action) {
      $action->buildForm($this);
      $action_list[$action->getID()] = array(
        'name' => $action->getName(),
        'tpl'  => $action->getFormTemplate()
);
    }
    $this->assign('action_list', $action_list);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => $task_id ? E::ts('Save') : E::ts('Create'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }

  /**
   * set the default (=current) values in the form
   */
  public function setDefaultValues() {
    $current_values = array();
    $current_values['name'] = $this->task->getAttribute('name');
    $current_values['description'] = $this->task->getAttribute('description');
    $current_values['category'] = $this->task->getAttribute('category');
    $current_values['scheduled'] = $this->task->getAttribute('scheduled');
    $current_values['parallel_exec'] = $this->task->getAttribute('parallel_exec');
    $current_values['main_sql'] = $this->task->getAttribute('main_sql');
    $current_values['post_sql'] = $this->task->getAttribute('post_sql');

    $configuration = $this->task->getConfiguration();
    foreach ($configuration as $key => $value) {
      $current_values[$key] = $value;
    }
    return $current_values;
  }


  /**
   * store the data
   */
  public function postProcess() {
    $values = $this->exportValues();

    // clean out some stuff
    $data = $values;
    if (isset($data['_qf_Configure_submit'])) {
      unset($data['_qf_Configure_submit']);
    }
    if (isset($data['_qf_default'])) {
      unset($data['_qf_default']);
    }
    if (isset($data['qfKey'])) {
      unset($data['qfKey']);
    }
    if (isset($data['entryURL'])) {
      unset($data['entryURL']);
    }
    if (isset($data['tid'])) {
      unset($data['tid']);
    }

    // write to DB
    $task_id = CRM_Utils_Array::value('tid', $values);
    $task = new CRM_Sqltasks_Task($task_id, $data);
    $task->store();

    // CRM_Core_Session::setStatus(E::ts('You picked color "%1"', array(
    //   1 => $options[$values['favorite_color']],
    // )));

    parent::postProcess();
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/sqltasks/manage'));
  }

}
