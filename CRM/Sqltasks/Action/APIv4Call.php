<?php

use CRM_Sqltasks_ExtensionUtil as ExtensionUtil;

class CRM_Sqltasks_Action_APIv4Call extends CRM_Sqltasks_Action {
  use CRM_Sqltasks_TempTableAlterations;

  const ACTION_ID = 'api_v4';
  const ACTION_NAME = 'APIv4 Call';
  const API_RESULT_COLUMN = 'sqltask_api_result';
  const DEFAULT_TEMPLATE_ORDER = 350;
  const ERROR_HANDLING_LOG_ONLY = 'log_only';
  const ERROR_HANDLING_REPORT_AND_CONTINUE = 'report_error_and_continue';
  const ERROR_HANDLING_REPORT_AND_ABORT = 'report_error_and_abort';

  private $errorHandlingMode;
  private $fails;
  private $skip;
  private $skipCount;
  private $successCount;

  public function execute() {
    $this->resetResultCounts();

    $this->errorHandlingMode = $this->getConfigValue('handle_api_errors');
    $entity = $this->getConfigValue('entity');
    $action = $this->getConfigValue('action');
    $dataTable = $this->getConfigValue('table');
    $paramsEncoded = $this->getConfigValue('parameters') ?? [];
    $params = json_decode($paramsEncoded, TRUE, 512, JSON_THROW_ON_ERROR);
    $storeApiResults = $this->getConfigValue('store_api_results');

    if ($storeApiResults) {
      $dataTableAutoIncCol = self::addAutoIncrementColumn($dataTable);
      $this->addApiResultColumn($dataTable, self::API_RESULT_COLUMN);
    }

    $query = CRM_Core_DAO::executeQuery("SELECT * FROM `$dataTable`");

    while ($query->fetch()) {
      if ($this->skip) {
        $this->skipCount++;
        continue;
      }

      if (property_exists($query, 'exclude') && $query->exclude) continue;

      $paramsSubstituted = $this->substitueParameterVariables($params, $query);

      try {
        $result = civicrm_api4($entity, $action, $paramsSubstituted);

        if ($storeApiResults) {
          $recordID = $query->$dataTableAutoIncCol;
          $resultJSON = json_encode($result);
          $apiResultColumn = self::API_RESULT_COLUMN;

          CRM_Core_DAO::executeQuery(
            "UPDATE `$dataTable` SET `$apiResultColumn` = %1 WHERE `$dataTableAutoIncCol` = $recordID",
            [ 1 => [$resultJSON, 'String'] ]
          );
        }

        $this->successCount++;
      } catch (Exception $ex) {
        $this->handleError($ex);
      }
    }

    $query->free();

    $this->logActionResults();
  }

  public function getID() {
    return self::ACTION_ID;
  }

  public function getName() {
    return ExtensionUtil::ts(self::ACTION_NAME);
  }

  public function getDefaultOrder() {
    return self::DEFAULT_TEMPLATE_ORDER;
  }

  private function handleError(Exception $exception) {
    $msg = $exception->getMessage();

    if (empty($this->fails[$msg])) {
      $this->fails[$msg] = 0;
    }

    $this->fails[$msg]++;

    switch ($this->errorHandlingMode) {
      case self::ERROR_HANDLING_LOG_ONLY: {
        // Nothing to do here
        break;
      }
      case self::ERROR_HANDLING_REPORT_AND_CONTINUE: {
        $this->reportError();
        break;
      }
      case self::ERROR_HANDLING_REPORT_AND_ABORT: {
        $this->reportError();
        $this->skip = TRUE;
        break;
      }
    }
  }

  private function logActionResults() {
    $this->log("{$this->successCount} API call(s) successfull.");

    foreach ($this->fails as $errMsg => $count) {
      $this->log("{$count} API call(s) FAILED with message: '{$errMsg}'");
    }

    if ($this->skipCount > 0) {
      $this->log("{$this->skipCount} API call(s) SKIPPED due to previous error.");
    }
  }

  private function reportError() {
    $this->context['execution']->reportError();
  }

  private function substitueParameterVariables($params, $record) {
    foreach ($params as $key => $value) {
      if (is_string($value)) {
        $params[$key] = $this->resolveTokens($value, $record);
        $params[$key] = $this->resolveGlobalTokens($params[$key]);
      }

      if (is_array($value)) {
        $params[$key] = $this->substitueParameterVariables($value, $record);
      }
    }

    return $params;
  }

  private function resetResultCounts() {
    $this->fails = [];
    $this->skip = FALSE;
    $this->skipCount = 0;
    $this->successCount = 0;
  }
}
