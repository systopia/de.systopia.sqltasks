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
   * get the table with the contact_id column
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
   * get a list of (param, value) definitions
   */
  protected function getParameters() {
    $parameters = array();
    $paremeters_spec = trim($this->getConfigValue('parameters'));
    $spec_lines = explode(PHP_EOL, $paremeters_spec);
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
   * generate a parameter set for the given data row
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
    // API Call specs
    $this->resetHasExecuted();
    $entity = $this->getConfigValue('entity');
    $action = $this->getConfigValue('action');
    $parameter_specs = $this->getParameters();

    // statistics
    $success_counter = 0;
    $fails = array();
    $more_fails_counter = 0;

    $data_table = $this->getDataTable();
    $excludeSql = '';
    if ($this->_columnExists($data_table, 'exclude')) {
      $excludeSql = 'WHERE (exclude IS NULL OR exclude != 1)';
      $this->log('Column "exclude" exists, might skip some rows');
    }
    $query = CRM_Core_DAO::executeQuery("SELECT * FROM {$data_table} {$excludeSql}");
    while ($query->fetch()) {
      $this->setHasExecuted();
      $parameters = $this->fillParameters($parameter_specs, $query);
      try {
        // error_log("Calling {$entity}.{$action}: " . json_encode($parameters));
        $result = civicrm_api3($entity, $action, $parameters);
      } catch (Exception $e) {
        $result = array('is_error'  => 1,
                        'error_msg' => $e->getMessage());
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
  }
}
