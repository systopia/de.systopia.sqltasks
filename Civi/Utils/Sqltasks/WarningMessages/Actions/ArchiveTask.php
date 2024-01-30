<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use Civi\Api4;
use CRM_Sqltasks_BAO_SqlTask;
use CRM_Utils_Type;

class ArchiveTask extends Base {

  protected $requiredActionDataFields = [
    'taskId' => CRM_Utils_Type::T_INT,
  ];

  /**
   * @param $data
   * @return array
   */
  public function handleWarningWindowData($data) {
    $task_id = $this->params['action_data']['taskId'] ?? NULL;
    $dep_task_ids = isset($task_id) ? CRM_Sqltasks_BAO_SqlTask::getDependentTasks($task_id) : [];

    if (empty($dep_task_ids)) {
      $data['isAllowDoAction'] = TRUE;
      return $data;
    }

    $data['warningWindow']['title'] = 'Archiving task';
    $data['warningWindow']['isShowYesButton'] = FALSE;
    $data['warningWindow']['message'] = '<p>This task is used by another task. Please remove the task from the following task(s) before archiving it:</p>';
    $data['warningWindow']['message'] .= $this->prepareTaskLinks($dep_task_ids);

    return $data;
  }

}
