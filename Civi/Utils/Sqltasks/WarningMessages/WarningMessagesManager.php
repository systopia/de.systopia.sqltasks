<?php

namespace Civi\Utils\Sqltasks\WarningMessages;

use Civi\Utils\Sqltasks\WarningMessages\Actions\ArchiveTask;
use Civi\Utils\Sqltasks\WarningMessages\Actions\DefaultAction;
use Civi\Utils\Sqltasks\WarningMessages\Actions\DeleteTask;
use Civi\Utils\Sqltasks\WarningMessages\Actions\DisableTask;
use Civi\Utils\Sqltasks\WarningMessages\Actions\EnableTask;
use Civi\Utils\Sqltasks\WarningMessages\Actions\ShowWhereTaskIsUsed;

class WarningMessagesManager {

  /**
   * @param $params
   * @return array
   * @throws \Exception
   */
  public static function getResult($params) {
    if ($params['action'] === 'archiveTask' && $params['context'] === 'sqlTaskManager') {
      return (new ArchiveTask($params))->getResult();
    }

    if ($params['action'] === 'deleteTask' && $params['context'] === 'sqlTaskManager') {
      return (new DeleteTask($params))->getResult();
    }

    if ($params['action'] === 'disableTask' && $params['context'] === 'sqlTaskManager') {
      return (new DisableTask($params))->getResult();
    }

    if ($params['action'] === 'enableTask' && $params['context'] === 'sqlTaskManager') {
      return (new EnableTask($params))->getResult();
    }

    if ($params['action'] === 'showWhereTaskIsUsed' && $params['context'] === 'sqlTaskManager') {
      return (new ShowWhereTaskIsUsed($params))->getResult();
    }

    return (new DefaultAction($params))->getResult();
  }

}
