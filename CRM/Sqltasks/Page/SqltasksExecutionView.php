<?php
use CRM_Sqltasks_ExtensionUtil as E;

class CRM_Sqltasks_Page_SqltasksExecutionView extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('Sqltasks Execution Logs'));

    $sqltasksExecutionId = CRM_Utils_Request::retrieve('id', 'Positive');
    if (empty($sqltasksExecutionId)) {
      throw new Exception('Cannot find the sqltasksExecution.');
    }

    $sqltasksExecution = CRM_Sqltasks_BAO_SqltasksExecution::getById($sqltasksExecutionId);
    if (empty($sqltasksExecution)) {
      throw new Exception('Cannot find the sqltasksExecution.');
    }

    $task = CRM_Sqltasks_Task::getTask($sqltasksExecution['sqltask_id']);
    $manageSqlTaskUrl = CRM_Utils_System::url('civicrm/a/', NULL, TRUE, "/sqltasks/configure/{$sqltasksExecution['sqltask_id']}");

    $this->assign('manageSqlTaskUrl', $manageSqlTaskUrl);
    $this->assign('task', $task->getAttributes());
    $this->assign('taskId', $task->getID());
    $this->assign('sqltasksExecution', $sqltasksExecution);
    $this->assign('logsTaskExecution', $sqltasksExecution['decoded_logs']);

    parent::run();
  }

}
