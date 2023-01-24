<?php

use CRM_Sqltasks_ExtensionUtil as E;

class CRM_Sqltasks_Form_SqltasksExecutionList extends CRM_Core_Form {

  const DEFAULT_LIMIT_PER_PAGE = 50;

  protected $searchParams = [
    'order_by' => ['id' => 'DESC'],//ASC DESC
    'error_status' => 'all',
  ];

  public function preProcess() {
    $this->setTitle(E::ts('Sqltasks Execution List'));
    $this->setSearchParams();
    $this->assign('sqltasksExecutions', CRM_Sqltasks_BAO_SqltasksExecution::getAll($this->searchParams));
    $summary = CRM_Sqltasks_BAO_SqltasksExecution::getSummary($this->searchParams);
    $this->assign('summary', $summary);
    $sqltasksExecutionsCount = $summary['count'];
    $this->assign('sqltasksExecutionsCount', $sqltasksExecutionsCount);
    $this->assign('pagination', $this->generatePaginationData($this->searchParams, $sqltasksExecutionsCount));
  }

  public function setDefaultValues() {
    return $this->searchParams;
  }

  public function buildQuickForm() {
    $this->add('select', 'sqltask_id', E::ts('SQL Task ID'), CRM_Sqltasks_Task::getTaskOptions(), FALSE, ['class' => 'crm-select2 huge', 'placeholder' => E::ts('- any -')]);
    $this->add('text', 'input', E::ts('Input value'), ['class' => 'medium', 'placeholder' => 'input value']);
    $this->addRadio('error_status', E::ts('Error Status'), [
      'all' => 'Show all executions',
      'only_errors' => 'With errors',
      'no_errors' => 'No errors',
    ]);
    $this->addEntityRef('created_id', E::ts('Contact ID of task executor'), ['class' => 'huge', 'placeholder' => '- any -']);
    $this->add('datepicker', 'from_start_date', E::ts('From start date'), ['class' => 'medium'], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'to_start_date', E::ts('To start date'), ['class' => 'medium'], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'from_end_date', E::ts('From end date'), ['class' => 'medium'], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'to_end_date', E::ts('To end date'), ['class' => 'medium'], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'end_date', E::ts('End date'), ['class' => 'medium'], FALSE, ['time' => FALSE]);
    $this->add('number', 'limit_per_page', ts('Limit per page'), ['class' => 'medium'], FALSE);
    $this->addButtons([['type' => 'submit', 'name' => E::ts('Submit'), 'isDefault' => TRUE]]);
  }

  public function postProcess() {
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
    $createdId = CRM_Utils_Request::retrieve('created_id', 'Integer');
    if (!empty($createdId)) {
      $this->searchParams['created_id'] = $createdId;
    }

    $limitPerPage = CRM_Utils_Request::retrieve('limit_per_page', 'Integer');
    if (!empty($limitPerPage)) {
      $this->searchParams['limit_per_page'] = $limitPerPage;
    } else {
      $this->searchParams['limit_per_page'] = CRM_Sqltasks_Form_SqltasksExecutionList::DEFAULT_LIMIT_PER_PAGE;
    }

    $pageNumber = CRM_Utils_Request::retrieve('page_number', 'Integer');
    if (!empty($pageNumber)) {
      $this->searchParams['page_number'] = $pageNumber;
    } else {
      $this->searchParams['page_number'] = 1;
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

    $error_status = CRM_Utils_Request::retrieve('error_status', 'String');
    if (!empty($error_status)) {
      $this->searchParams['error_status'] = $error_status;
    }
  }

  protected function generatePaginationData($searchParams, $allItemsCount) {
    if ((int) $searchParams['limit_per_page'] >= $allItemsCount) {
      return null;
    }

    $currentPage = (int) $searchParams['page_number'];
    $limitPerPage = (int) $searchParams['limit_per_page'];
    $nextPageNumber = $currentPage + 1;
    $prevPageNumber = $currentPage - 1;
    $firstPageNumber = 1;
    $maxPageNumber = (int) floor(($allItemsCount % $limitPerPage !== 0) ? $allItemsCount / $limitPerPage + 1 : $allItemsCount / $limitPerPage);
    $lastPageNumber = $maxPageNumber;
    $showToCount = (int) ($currentPage === $lastPageNumber) ? $allItemsCount : $limitPerPage * $currentPage;
    $showFromCount = (int) (($currentPage - 1) * $limitPerPage + 1);

    $data = [
      'all_count' => $allItemsCount,
      'limit_per_page' => $limitPerPage,
      'current_page_number' => $currentPage,
      'max_page_number' => $maxPageNumber,
      'show_from_count' => $showFromCount,
      'show_to_count' => $showToCount,
      'next_link' => null,
      'prev_link' => null,
      'last_link' => null,
      'first_link' => null,
    ];

    if ($nextPageNumber <= $maxPageNumber) {
      $data['next_link'] = CRM_Utils_System::url('civicrm/sqltasks-execution/list', http_build_query(array_merge($searchParams, ['page_number' => $nextPageNumber])));
    }

    if ($prevPageNumber >= 1) {
      $data['prev_link'] = CRM_Utils_System::url('civicrm/sqltasks-execution/list', http_build_query(array_merge($searchParams, ['page_number' => $prevPageNumber])));
    }

    if ($lastPageNumber !== $currentPage) {
      $data['last_link'] = CRM_Utils_System::url('civicrm/sqltasks-execution/list', http_build_query(array_merge($searchParams, ['page_number' => $lastPageNumber])));
    }

    if ($firstPageNumber !== $currentPage) {
      $data['first_link'] = CRM_Utils_System::url('civicrm/sqltasks-execution/list', http_build_query(array_merge($searchParams, ['page_number' => $firstPageNumber])));
    }

    return $data;
  }

}
