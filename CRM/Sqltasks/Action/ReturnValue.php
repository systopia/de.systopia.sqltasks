<?php

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * This actions allows you to get value from specified db table
 *
 */
class CRM_Sqltasks_Action_ReturnValue extends CRM_Sqltasks_Action {

  /**
   * Log only
   * Is default
   * (this is setting option which set how to handle API errors)
   */
  const LOG_ONLY = 'log_only';

  /**
   * Report task error and continue API calls
   * (this is setting option which set how to handle API errors)
   */
  const REPORT_ERROR_AND_CONTINUE = 'report_error_and_continue';

  /**
   * Report task error and abort API calls
   * (this is setting option which set how to handle API errors)
   */
  const REPORT_ERROR_AND_ABORT = 'report_error_and_abort';

  const API_RESULT_COLUMN = 'value';

  public $return_key;
  public $return_value;


  /**
   * Get identifier string
   */
  public function getID() {
    return 'return-value';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('Return Value');
  }

  /**
   * Get default template order
   *
   * @return int
   */
  public function getDefaultOrder() {
    return 1000;
  }

  /**
   * Whether this action should be included in the template for new tasks
   *
   * @return bool
   */
  public static function isDefaultTemplateAction() {
    return FALSE;
  }

  /**
   * Get the table with the contact_id column
   */
  protected function getDataTable() {
    $table_name = $this->getConfigValue('table');
    return trim($table_name);
  }

  /**
   * Check if this action is configured correctly
   */
  public function checkConfiguration() {
    parent::checkConfiguration();

    $data_table = $this->getDataTable();
    if (empty($data_table)) {
      throw new Exception("Data table not configured.", 1);
    }

    // check if table exists
    $existing_table = CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '{$data_table}';");
    if (!$existing_table) {
      throw new Exception("Table '{$data_table}' doesn't exist.", 1);
    }

    // check if parameter is set
    $parameter = $this->getConfigValue('parameter');
    if (empty($parameter)) {
      throw new Exception("Parameter not set", 1);
    }

    $reserved_parameters = ['log', 'files', 'runtime'];
    if (in_array($parameter, $reserved_parameters)) {
      throw new Exception("Parameter's name use reserved word", 1);
    }
  }

  /**
   * RUN this action
   *
   * @throws \Exception
   */
  public function execute() {
    $handle_api_errors = $this->getHandleApiErrors();

    // API Call specs
    //$this->resetHasExecuted();

    $data_table = $this->getDataTable();
    $query = CRM_Core_DAO::executeQuery("SELECT `". self::API_RESULT_COLUMN ."` FROM `{$data_table}`");

    $this->return_key = $this->getConfigValue('parameter');
    $this->return_value = $query->fetchValue();
  }

  /**
   * Get all possible options of handling API errors
   *
   * @return array
   */
  public static function getHandleApiErrorsOptions() {
    return [
      self::LOG_ONLY => E::ts('Log only'),
      self::REPORT_ERROR_AND_CONTINUE => E::ts('Report task error and continue API calls'),
      self::REPORT_ERROR_AND_ABORT => E::ts('Report task error and abort API calls'),
    ];
  }

  /**
   * Gets handle Api errors value
   * If the value is empty or not valid it returns default value
   *
   * @return string
   */
  private function getHandleApiErrors() {
    $handle_api_errors = $this->getConfigValue('handle_api_errors');
    if (key_exists($handle_api_errors, self::getHandleApiErrorsOptions())) {
      return $handle_api_errors;
    }

    return self::LOG_ONLY;
  }

  /**
   * Report API call error
   *
   */
  protected function reportError() {
    $this->task->incrementErrorCounter();
    $this->task->setErrorStatus();
  }

}
