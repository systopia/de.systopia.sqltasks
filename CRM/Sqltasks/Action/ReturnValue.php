<?php

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * This actions allows you to get value from specified db table
 *
 */
class CRM_Sqltasks_Action_ReturnValue extends CRM_Sqltasks_Action {

  const API_RESULT_COLUMN = 'value';

  /**
   * ReturnValue Key
   */
  public $return_key;

  /**
   * ReturnValue Value
   */
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
   * Get the table with "API_RESULT_COLUMN" column
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
    $data_table = $this->getDataTable();
    $query = CRM_Core_DAO::executeQuery("SELECT `". self::API_RESULT_COLUMN ."` FROM `{$data_table}`");

    $this->return_key = $this->getConfigValue('parameter');
    $this->return_value = $query->fetchValue();

    $this->log("Set return value as '{$this->return_key}' => '{$this->return_value}'");
  }

}
