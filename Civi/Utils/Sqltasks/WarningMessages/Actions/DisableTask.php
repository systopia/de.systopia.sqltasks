<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_Task;
use CRM_Utils_Type;

class DisableTask extends Base {

  protected $requiredActionDataFields = [
    'taskId' => CRM_Utils_Type::T_INT,
  ];

  public function handleWarningWindowData($data) {
    $toggleTaskData = CRM_Sqltasks_Task::getDataAboutIfAllowToToggleTask($this->params['action_data']['taskId']);
    $disableTaskData = $toggleTaskData['disabling'];
    if ($disableTaskData['isAllow']) {
      $data['isAllowDoAction'] = true;
      return $data;
    }

    $data['warningWindow']['title'] = 'Disabling task';
    $data['warningWindow']['message'] = '';
    $data['warningWindow']['isShowYesButton'] = false;

    if (!empty($disableTaskData['notSkippedRelatedTasks'])) {
      $data['warningWindow']['message'] .= '<p>This task is used by another task. Please remove the task from the following task(s) before disabling it:</p>';
      $data['warningWindow']['message'] .= $this->prepareTaskLinks($disableTaskData['notSkippedRelatedTasks']);
    }

    if (!empty($disableTaskData['skippedRelatedTasks'])) {
      $data['warningWindow']['message'] .= '<p>This task is used by another task. But you can continue ...</p>';
      $data['warningWindow']['message'] .= $this->prepareTaskLinks($disableTaskData['skippedRelatedTasks']);
    }

    return $data;
  }

}
