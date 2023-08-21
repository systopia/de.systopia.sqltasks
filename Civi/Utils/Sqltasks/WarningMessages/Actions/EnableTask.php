<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_Task;
use CRM_Utils_Type;

class EnableTask extends Base {

  protected $requiredActionDataFields = [
    'taskId' => CRM_Utils_Type::T_INT,
  ];

  public function handleWarningWindowData($data) {
    // TODO: only trigger this warning if:
    // - the task is used by another task, AND
    // - the other task uses is_execute_disabled_tasks = 0
    $taskIds = CRM_Sqltasks_Task::findTaskIdsWhichUsesTask($this->params['action_data']['taskId']);
    if (empty($taskIds)) {
      $data['isAllowDoAction'] = true;
      return $data;
    }

    $data['warningWindow']['title'] = 'Enabling task';
    $data['warningWindow']['isShowYesButton'] = true;
    $data['warningWindow']['message'] = '<p>When you enable this task, it may be executed automatically by the following task(s):</p>';
    $data['warningWindow']['message'] .= $this->prepareTaskLinks(CRM_Sqltasks_Task::getTaskObjectsByIds($taskIds));

    return $data;
  }

}
