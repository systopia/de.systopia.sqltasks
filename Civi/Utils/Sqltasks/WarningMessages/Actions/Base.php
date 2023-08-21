<?php

namespace Civi\Utils\Sqltasks\WarningMessages\Actions;

use CRM_Sqltasks_Task;
use CRM_Utils_Type;
use Exception;

class Base {

  protected $requiredActionDataFields = [];
  private $rawParams = [];
  protected $params = [];

  /**
   * @param $rawParams
   * @throws Exception
   */
  public function __construct($rawParams) {
    $this->rawParams = $rawParams;
    $this->prepareParams();
  }

  /**
   * @return array
   */
  public function getResult() {
    $warningWindowData = $this->getDefaultWarningWindowData();

    return $this->handleWarningWindowData($warningWindowData);
  }

  /**
   * @param $data
   * @return mixed
   */
  protected function handleWarningWindowData($data) {
    return $data;
  }

  /**
   * @return array
   */
  private function getDefaultWarningWindowData() {
    return [
      'isAllowDoAction' => false,
      'warningWindow' => [
        'title' => $this->params['action'],
        'message' => 'Do "' . $this->params['action'] . '" action?',
        'yesButtonText' => 'Continue',
        'yesButtonClasses' => '',
        'isShowYesButton' => true,
        'yesButtonIcon' => 'fa-check',
        'noButtonText' => 'Cancel',
        'noButtonClasses' => '',
        'noButtonIcon' => 'fa-times',
      ]
    ];
  }

  /**
   * @return void
   * @throws Exception
   */
  private function prepareParams() {
    $this->params['action'] = (string) $this->rawParams['action'];
    $this->params['context'] = (string) $this->rawParams['context'];
    $this->params['action_data'] = [];

    if (empty($this->requiredActionDataFields)) {
      return;
    }

    if (empty($this->rawParams['action_data'])) {
      throw new Exception('"action_data" is required field!');
    }

    foreach ($this->requiredActionDataFields as $requiredFieldName => $requiredFieldType) {
      if (empty($this->rawParams['action_data'][$requiredFieldName])) {
        throw new Exception('"action_data.' . $requiredFieldName . '" is required field!');
      }

      if ($requiredFieldType === CRM_Utils_Type::T_INT) {
        $this->params['action_data'][$requiredFieldName] = (int) $this->rawParams['action_data'][$requiredFieldName];
      } elseif ($requiredFieldType === CRM_Utils_Type::T_STRING) {
        $this->params['action_data'][$requiredFieldName] = (string) $this->rawParams['action_data'][$requiredFieldName];
      }
    }
  }

  /**
   * @param array $taskObjects
   * @return string
   */
  protected function prepareTaskLinks($taskObjects) {
    $linksHtml = '<ul>';
    foreach ($taskObjects as $task) {
      $linksHtml .= '<li><a target="_blank" href="' . $task->getConfigureTaksLink() . '">';
      $linksHtml .=  '[' . $task->getID() . '] ' . $task->getAttribute('name');
      $linksHtml .= '</a></li>';
    }
    $linksHtml .= '</ul>';

    return $linksHtml;
  }

}
