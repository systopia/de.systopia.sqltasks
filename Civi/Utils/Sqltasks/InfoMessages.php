<?php

namespace Civi\Utils\Sqltasks;

use Civi;
use Civi\Utils\CiviCRM_API3_Exception;
use CRM_Utils_System;

class InfoMessages {

  private $infoMessages = [];

  /**
   * Get all info messages for sqltask manager
   *
   * @return array
   */
  public function getAll() {
    $this->prepareDispatcherMessages();

    return $this->infoMessages;
  }

  private function prepareDispatcherMessages() {
    $dispatcherData = $this->getDispatcherData();

    if (empty($dispatcherData)) {
      $this->addMessage('Dispatcher doesn\'t exist.', 'error');
    }

    if ($dispatcherData['is_active'] != 1) {
      $this->addMessage('The dispatcher is currently <strong>disabled</strong>, none of the tasks will be executed automatically.');
      return;
    }

    if (Settings::isDispatcherDisabled()) {
      $settingsLink = CRM_Utils_System::url('civicrm/sqltask/settings', 'reset=1');
      $message = 'The dispatcher is currently <strong>disabled</strong> due to task execution errors. ';
      $message .= 'To enable it, got to the ';
      $message .= '<a class="crm-link" target="_blank" href="' . $settingsLink . '" >SQL Task Settings</a>';
      $message .= ' page.';
      $this->addMessage($message, 'error');
      return;
    }

    if ($dispatcherData['run_frequency'] === 'Always') {
      $message = 'The dispatcher (and therefore all active tasks) will be triggered <strong>with every cron-run</strong>.';
      $message .= 'Ask your administrator how often that is, ';
      $message .= 'in order to know the effective maximum frequency these tasks are being executed with.';
      $this->addMessage($message);
    } elseif ($dispatcherData['run_frequency'] === 'Daily') {
      $message = 'The dispatcher is run <strong>every day</strong> after midnight.';
      $message .= 'This is effectively the maximum frequency these tasks are being executed with.';
      $this->addMessage($message);
    } elseif ($dispatcherData['run_frequency'] === 'Hourly') {
      $message = 'The dispatcher is run <strong>every hour</strong> on the hour.';
      $message .= 'This is effectively the maximum frequency these tasks are being executed with.';
      $this->addMessage($message);
    } else {
      $this->addMessage('Unexpected run frequency: ' . $dispatcherData['run_frequency'], 'error');
    }
  }

  /**
   * @return null|array
   */
  protected function getDispatcherData() {
    try {
      $job = civicrm_api3('Job', 'get', [
          'sequential' => 1,
          'api_entity' => "Sqltask",
          'api_action' => "execute",
      ]);

      return !empty($job['values'][0]) ? $job['values'][0] : null;
    } catch (CiviCRM_API3_Exception $e) {
      $this->addMessage('Error(Job.get) while getting dispatcher data:' . $e->getMessage(), 'error');
    }

    return null;
  }

  /**
   * @param $message
   * @param $type
   * @return void
   */
  private function addMessage($message, $type = 'info') {
    $this->infoMessages[] = [
        'text' => $message,
        'type' => $type,
    ];
  }

}
