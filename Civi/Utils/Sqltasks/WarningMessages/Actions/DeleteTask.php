<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_Task;
use CRM_Utils_Type;

class DeleteTask extends Base {

  protected $requiredActionDataFields = [
    'taskId' => CRM_Utils_Type::T_INT,
  ];

  public function handleWarningWindowData($data) {
    $taskIds = CRM_Sqltasks_Task::findTaskIdsWhichUsesTask($this->params['action_data']['taskId']);
    if (empty($taskIds)) {
      $data['isAllowDoAction'] = true;
      return $data;
    }

    $data['warningWindow']['title'] = 'Deleting task';
    $data['warningWindow']['isShowYesButton'] = false;
    $data['warningWindow']['message'] = '<p>This task is used by another task. Please remove the task from the following task(s) before deleting it:</p>';
    $data['warningWindow']['message'] .= $this->prepareTaskLinks(CRM_Sqltasks_Task::getTaskObjectsByIds($taskIds));

    return $data;
  }

}
