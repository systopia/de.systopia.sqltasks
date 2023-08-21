<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_Task;
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
    $taskIds = CRM_Sqltasks_Task::findTaskIdsWhichUsesTask($this->params['action_data']['taskId']);
    if (empty($taskIds)) {
      $data['isAllowDoAction'] = true;
      return $data;
    }

    $data['warningWindow']['title'] = 'Archiving task';
    $data['warningWindow']['isShowYesButton'] = false;
    $data['warningWindow']['message'] = '<p>You cannon archive this task. This task is used in another tasks. Please remove this task from another tasks:</p>';
    $data['warningWindow']['message'] .= $this->prepareTaskLinks(CRM_Sqltasks_Task::gerTaskObjectsByIds($taskIds));

    return $data;
  }

}
