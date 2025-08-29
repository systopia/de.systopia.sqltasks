<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_BAO_SqlTask;
use CRM_Utils_Type;

class ShowWhereTaskIsUsed extends Base {

  protected $requiredActionDataFields = [
    'taskId' => CRM_Utils_Type::T_INT,
  ];

  public function handleWarningWindowData($data) {
    $task_id = $this->params['action_data']['taskId'] ?? NULL;
    $dep_task_ids = isset($task_id) ? CRM_Sqltasks_BAO_SqlTask::getDependentTasks($task_id) : [];

    $data['warningWindow']['title'] = 'Where tasks is used:';
    $data['warningWindow']['isShowYesButton'] = FALSE;
    $data['warningWindow']['noButtonText'] = 'Back';

    if (empty($dep_task_ids)) {
      $data['warningWindow']['message'] = "<p style='width: 300px;'>This task is not used by other tasks.</p>";
      return $data;
    }

    $data['warningWindow']['message'] = '<p style="width: 300px;">This task is used by the following task(s):</p>';
    $data['warningWindow']['message'] .= $this->prepareTaskLinks($dep_task_ids);

    return $data;
  }

}
