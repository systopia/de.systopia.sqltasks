<?php
class CRM_Sqltasks_Page_TaskLatestLogs extends CRM_Core_Page {
  public function run() {
    $taskId = CRM_Utils_Request::retrieve('sqltask_id', 'Integer');
    if (empty($taskId)) {
      throw new Exception('"sqltask_id" is required params');
    }

    $sqltasksExecutionId = CRM_Sqltasks_BAO_SqltasksExecution::getTheLatestExecutionId($taskId);
    if (empty($sqltasksExecutionId)) {
      throw new Exception('Cannot find any logs for sqltask id=.' . $taskId);
    }

    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/sqltasks-execution/view', ['id' => $sqltasksExecutionId]));
  }

}
