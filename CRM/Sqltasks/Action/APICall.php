<?php
/*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

define('FAIL_MESSAGE_COUNT', 5);

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * This actions allows you to synchronise
 *  a resulting contact set with a group
 *
 */
class CRM_Sqltasks_Action_APICall extends CRM_Sqltasks_Action {

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

  /**
   * Get identifier string
   */
  public function getID() {
    return 'api';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('API Call');
  }

  /**
   * Get default template order
   *
   * @return int
   */
  public function getDefaultOrder() {
    return 300;
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
      throw new Exception("Export Table '{$data_table}' doesn't exist.", 1);
    }

    // check if entity/action are set
    $entity = $this->getConfigValue('entity');
    if (empty($entity)) {
      throw new Exception("API Entity not set", 1);
    }
    $action = $this->getConfigValue('action');
    if (empty($action)) {
      throw new Exception("API action not set", 1);
    }
  }

  /**
   * Get a list of (param, value) definitions
   */
  protected function getParameters() {
    $parameters = array();
    $parameters_spec = trim($this->getConfigValue('parameters'));
    $spec_lines = explode(PHP_EOL, $parameters_spec);
    foreach ($spec_lines as $spec_line) {
      $separator_index = strpos($spec_line, '=');
      if ($separator_index > 0) {
        $parameter = trim(substr($spec_line, 0, $separator_index));
        $value = trim(substr($spec_line, $separator_index + 1));
        if (!empty($parameter) && !empty($value)) {
          $parameters[$parameter] = $value;
        }
      } else {
        // this line is ignored, it doesn't have the asdasd=asdasd form
      }
    }

    return $parameters;
  }

  /**
   * Generate a parameter set for the given data row
   */
  protected function fillParameters($specs, $data_row) {
    $parameters = array();
    foreach ($specs as $key => $value) {
      // calculate value by
      $param_value = $this->resolveTokens($value, $data_row);

      // check if this happens to be a complex JSON string (#50)
      $first_character = substr($param_value, 0, 1);
      if ($first_character == '[' || $first_character == '{' || $first_character == '"') {
        $json_value = json_decode($param_value, TRUE);
        if ($json_value !== NULL) {
          // json parsing worked, let's use it
          $param_value = $json_value;
        }
      }

      // set the value
      $parameters[$key] = $param_value;
    }
    return $parameters;
  }

  /**
   * RUN this action
   */
  public function execute() {
    $handle_api_errors = $this->getHandleApiErrors();

    // API Call specs
    $this->resetHasExecuted();
    $entity = $this->getConfigValue('entity');
    $action = $this->getConfigValue('action');
    $parameter_specs = $this->getParameters();

    // statistics
    $success_counter = 0;
    $fails = [];
    $more_fails_counter = 0;
    $skip_counter = 0;

    $data_table = $this->getDataTable();
    $excludeSql = '';
    $is_need_to_skip = false;
    if ($this->_columnExists($data_table, 'exclude')) {
      $excludeSql = 'WHERE (exclude IS NULL OR exclude != 1)';
      $this->log('Column "exclude" exists, might skip some rows');
    }
    $query = CRM_Core_DAO::executeQuery("SELECT * FROM {$data_table} {$excludeSql}");
    while ($query->fetch()) {
      if ($is_need_to_skip) {
        $skip_counter += 1;
        continue;
      }

      $this->setHasExecuted();
      $parameters = $this->fillParameters($parameter_specs, $query);
      try {
        $result = civicrm_api3($entity, $action, $parameters);
      } catch (Exception $e) {
        $result = [
          'is_error'  => 1,
          'error_msg' => $e->getMessage()
        ];

        if (in_array($handle_api_errors, [self::REPORT_ERROR_AND_CONTINUE, self::REPORT_ERROR_AND_ABORT])) {
            $this->reportError();
        }

        if ($handle_api_errors === self::REPORT_ERROR_AND_ABORT) {
          $this->log("API call failed. Next API call(s) will be skipped.");
          $is_need_to_skip = true;
        }
      }

      // process result
      if (empty($result['is_error'])) {
        $success_counter += 1;
      } else {
        // TODO: cap entry count?
        $error = $result['error_msg'];
        if (isset($fails[$error])) {
          $fails[$error] = $fails[$error] + 1;
        } else {
          if (count($fails) < FAIL_MESSAGE_COUNT) {
            $fails[$error] = 1;
          } else {
            // there's too many already -> just count generically
            $more_fails_counter += 1;
          }
        }
      }
    }
    // clear query
    $query->free();

    // create result
    $this->log("{$success_counter} API call(s) successfull.");
    foreach ($fails as $error => $counter) {
      $this->log("{$counter} API call(s) FAILED with message: '{$error}'");
    }
    if ($more_fails_counter) {
      $this->log("{$more_fails_counter} API call(s) FAILED with other messages.");
    }
    if ($skip_counter) {
      $this->log("{$skip_counter} API call(s) SKIPPED due to previous error.");
    }
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
