<?php

use CRM_Sqltasks_ExtensionUtil as E;

class CRM_Sqltasks_Form_SqltasksExecutionList extends CRM_Core_Form {

  protected $searchParams = [
    'order_by' => ['civicrm_sqltasks_execution.id' => 'DESC'],//ASC DESC
  ];

  public function preProcess() {
    $this->setTitle(E::ts('Sqltasks Execution List'));
    $this->setSearchParams();
    $this->assign('sqltasksExecutions', CRM_Sqltasks_BAO_SqltasksExecution::getAll($this->searchParams));
  }

  public function setDefaultValues() {
    return $this->searchParams;
  }

  public function buildQuickForm() {
    $this->add('select', 'sqltask_id', E::ts('Sqltask id'), CRM_Sqltasks_Task::getTaskOptions(), FALSE, ['class' => 'crm-select2 huge', 'placeholder' => E::ts('- any -')]);
    $this->add('text', 'input', ts('Input value'), ['class' => 'medium', 'placeholder' => 'input value']);
    $this->add('checkbox', 'is_has_errors', ts('Is has errors?'));
    $this->add('checkbox', 'is_has_no_errors', ts('Is has no errors?'));
    $this->addEntityRef('created_id', E::ts('Contact ID of task executor'), ['class' => 'huge', 'placeholder' => '- any -']);
    $this->add('datepicker', 'from_start_date', E::ts('From start date'), ['class' => 'medium'], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'to_start_date', E::ts('To start date'), ['class' => 'medium'], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'from_end_date', E::ts('From end date'), ['class' => 'medium'], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'to_end_date', E::ts('To end date'), ['class' => 'medium'], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'end_date', E::ts('End date'), ['class' => 'medium'], FALSE, ['time' => FALSE]);
    $this->addButtons([['type' => 'submit', 'name' => E::ts('Submit'), 'isDefault' => TRUE]]);
  }

  public function postProcess() {
    unset($this->searchParams['options']);
    $this->controller->setDestination(CRM_Utils_System::url('civicrm/sqltasks-execution/list', http_build_query($this->searchParams)));
  }

  public function setSearchParams() {
    $taskId = CRM_Utils_Request::retrieve('sqltask_id', 'Integer');
    if (!empty($taskId)) {
      $this->searchParams['sqltask_id'] = $taskId;
    }

    $inputValue = CRM_Utils_Request::retrieve('input', 'String');
    if (!empty($inputValue)) {
      $this->searchParams['input'] = $inputValue;
    }

    $isHasErrors = CRM_Utils_Request::retrieve('is_has_errors', 'Integer');
    if (!empty($isHasErrors) && $isHasErrors === 1) {
      $this->searchParams['is_has_errors'] = 1;
    }

    $createdId = CRM_Utils_Request::retrieve('created_id', 'Integer');
    if (!empty($createdId)) {
      $this->searchParams['created_id'] = $createdId;
    }

    $startDate = CRM_Utils_Request::retrieve('start_date', 'String');
    if (!empty($startDate)) {
      $this->searchParams['start_date'] = $startDate;
    }

    $fromStartDate = CRM_Utils_Request::retrieve('from_start_date', 'String');
    if (!empty($fromStartDate)) {
      $this->searchParams['from_start_date'] = $fromStartDate;
    }

    $toStartDate = CRM_Utils_Request::retrieve('to_start_date', 'String');
    if (!empty($toStartDate)) {
      $this->searchParams['to_start_date'] = $toStartDate;
    }

    $endDate = CRM_Utils_Request::retrieve('end_date', 'String');
    if (!empty($endDate)) {
      $this->searchParams['end_date'] = $endDate;
    }

    $isHasNoErrors = CRM_Utils_Request::retrieve('is_has_no_errors', 'Integer');
    if (!empty($isHasNoErrors) && $isHasNoErrors === 1) {
      $this->searchParams['is_has_no_errors'] = $isHasNoErrors;
    }
  }

}
