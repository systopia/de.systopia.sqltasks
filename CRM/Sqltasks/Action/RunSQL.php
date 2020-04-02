<?php
/*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2018 SYSTOPIA                            |
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

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * This actions allows you to run arbitrary SQL statements
 *
 */
class CRM_Sqltasks_Action_RunSQL extends CRM_Sqltasks_Action {

  /**
   * Get identifier string
   */
  public function getID() {
    return 'sql';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('Run SQL Script');
  }

  /**
   * Get default template order
   *
   * @return int
   */
  public function getDefaultOrder() {
    return 0;
  }

  /**
   * Whether this action should be included in the template for new tasks
   *
   * @return bool
   */
  public static function isDefaultTemplateAction() {
    return TRUE;
  }

  /**
   * Check if this action is configured correctly
   */
  public function checkConfiguration() {
    parent::checkConfiguration();
    $entity = $this->getConfigValue('script');
    if (empty($entity)) {
      throw new Exception('SQL Script not provided', 1);
    }
  }

  /**
   * RUN this action
   */
  public function execute() {
    // has_executed is always false for RunSQL
    $this->resetHasExecuted();
    try {
      // prepare
      $config = CRM_Core_Config::singleton();
      $script = html_entity_decode($this->getConfigValue('script'));
      if (!empty($this->context['input_val'])) {
        $input_val = CRM_Core_DAO::escapeString($this->context['input_val']);
        $script = "SET @input = '{$input_val}'; \r\n {$script}";
      }
      CRM_Utils_File::runSqlQuery($config->dsn, $script);
    }
    catch (Exception $e) {
      $message = $e->getMessage();
      if ($e instanceof PEAR_Exception && $e->getCause() instanceof DB_Error) {
      $message .= ' Details: ' . $e->getCause()->getUserInfo();
      }
      $this->log("SQL execution failed: " . $message);
      throw $e;
    }
  }

}
