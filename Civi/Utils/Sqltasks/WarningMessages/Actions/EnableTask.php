<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_Task;
use CRM_Utils_Type;

class EnableTask extends Base {

  protected $requiredActionDataFields = [
    'taskId' => CRM_Utils_Type::T_INT,
  ];

  public function handleWarningWindowData($data) {
    $taskIds = CRM_Sqltasks_Task::findTaskIdsWhichUsesTask($this->params['action_data']['taskId']);
    if (empty($taskIds)) {
      $data['isAllowDoAction'] = true;
      return $data;
    }

    $data['warningWindow']['title'] = 'Disabling task';
    $data['warningWindow']['isShowYesButton'] = true;
    $data['warningWindow']['message'] = '<p>Be careful, this task is used in another tasks. When you enable this task, it can be executed automatically by tasks:</p>';
    $data['warningWindow']['message'] .= $this->prepareTaskLinks(CRM_Sqltasks_Task::gerTaskObjectsByIds($taskIds));

    return $data;
  }

}
