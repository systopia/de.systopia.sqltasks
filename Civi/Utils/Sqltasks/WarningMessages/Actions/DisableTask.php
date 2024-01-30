<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_BAO_SqlTask;
use CRM_Utils_Type;

class DisableTask extends Base {

  protected $requiredActionDataFields = [
    'taskId' => CRM_Utils_Type::T_INT,
  ];

  public function handleWarningWindowData($data) {
    $task_id = $this->params['action_data']['taskId'] ?? NULL;
    $dep_task_ids = isset($task_id) ? CRM_Sqltasks_BAO_SqlTask::getDependentTasks($task_id) : [];

    if (empty($dep_task_ids)) {
      $data['isAllowDoAction'] = TRUE;
      return $data;
    }

    $data['warningWindow']['title'] = 'Disabling task';
    $data['warningWindow']['message'] = '';
    $data['warningWindow']['isShowYesButton'] = FALSE;

    // Are there other tasks, that need this task to be enabled?
    $enabled_dep_task_ids =
      isset($task_id)
      ? CRM_Sqltasks_BAO_SqlTask::getDependentTasks($task_id, TRUE)
      : [];

    if (empty($enabled_dep_task_ids)) {
      $data['warningWindow']['message'] .= '<p>This task is used by other tasks. But you can continue ...</p>';
      $data['warningWindow']['message'] .= $this->prepareTaskLinks($dep_task_ids);
      $data['warningWindow']['isShowYesButton'] = TRUE;
      return $data;
    }

    $data['warningWindow']['message'] .= '<p>This task is used by another task. Please remove the task from the following task(s) before disabling it:</p>';
    $data['warningWindow']['message'] .= $this->prepareTaskLinks($enabled_dep_task_ids);

    return $data;
  }

}
