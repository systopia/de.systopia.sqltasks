<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_Task;
use CRM_Utils_Type;

class EnableTask extends Base {

  protected $requiredActionDataFields = [
    'taskId' => CRM_Utils_Type::T_INT,
  ];

  public function handleWarningWindowData($data) {
    $toggleTaskData = CRM_Sqltasks_Task::getDataAboutIfAllowToToggleTask($this->params['action_data']['taskId']);
    $enableTaskData = $toggleTaskData['enabling'];
    if ($enableTaskData['isAllow']) {
      $data['isAllowDoAction'] = true;
      return $data;
    }

    $data['warningWindow']['title'] = 'Enabling task';
    $data['warningWindow']['isShowYesButton'] = true;
    $data['warningWindow']['message'] = '<p>When you enable this task, it may be executed automatically by the following task(s):</p>';
    $data['warningWindow']['message'] .= $this->prepareTaskLinks($enableTaskData['notSkippedRelatedTasks']);

    return $data;
  }

}
