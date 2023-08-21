<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_Task;
use CRM_Utils_Type;

class DisableTask extends Base {

  protected $requiredActionDataFields = [
    'taskId' => CRM_Utils_Type::T_INT,
  ];

  public function handleWarningWindowData($data) {
    $disableTaskData = CRM_Sqltasks_Task::getDataAboutIfAllowToDisableTask($this->params['action_data']['taskId']);
    if ($disableTaskData['isAllowToDisableTask']) {
      $data['isAllowDoAction'] = true;
      return $data;
    }

    $data['warningWindow']['title'] = 'Disabling task';
    $data['warningWindow']['message'] = '';
    $data['warningWindow']['isShowYesButton'] = false;

    if (!empty($disableTaskData['notSkippedRelatedTasks'])) {
      $data['warningWindow']['message'] .= '<p>You cannon disable this task. This task is used in another tasks. Please remove this task from another tasks:</p>';
      $data['warningWindow']['message'] .= $this->prepareTaskLinks($disableTaskData['notSkippedRelatedTasks']);
    }

    if (!empty($disableTaskData['skippedRelatedTasks'])) {
      $data['warningWindow']['message'] .= '<p>This task is used in another tasks, but can be left at task below. In those tasks current task will be executed even task are disabled.</p>';
      $data['warningWindow']['message'] .= $this->prepareTaskLinks($disableTaskData['skippedRelatedTasks']);
    }

    return $data;
  }

}
