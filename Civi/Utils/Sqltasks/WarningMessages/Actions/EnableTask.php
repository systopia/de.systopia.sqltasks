<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_BAO_SqlTask;
use CRM_Utils_Type;

class EnableTask extends Base {

  protected $requiredActionDataFields = [
    'taskId' => CRM_Utils_Type::T_INT,
  ];

  public function handleWarningWindowData($data) {
    $task_id = $this->params['action_data']['taskId'] ?? NULL;

    $dep_task_ids =
      isset($task_id)
      ? CRM_Sqltasks_BAO_SqlTask::getDependentTasks($task_id, TRUE)
      : [];

    if (empty($dep_task_ids)) {
      $data['isAllowDoAction'] = TRUE;
      return $data;
    }

    $data['warningWindow']['title'] = 'Enabling task';
    $data['warningWindow']['isShowYesButton'] = TRUE;
    $data['warningWindow']['message'] = '<p>When you enable this task, it may be executed automatically by the following task(s):</p>';
    $data['warningWindow']['message'] .= $this->prepareTaskLinks($dep_task_ids);

    return $data;
  }

}
