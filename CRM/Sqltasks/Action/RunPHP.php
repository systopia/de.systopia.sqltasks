<?php
/*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: P. Figel <pfigel@greenpeace.org>
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
 * This actions allows you to run arbitrary PHP code
 *
 */
class CRM_Sqltasks_Action_RunPHP extends CRM_Sqltasks_Action {

  /**
   * Get identifier string
   */
  public function getID() {
    return 'php';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('Run PHP Code');
  }

  /**
   * Get default template order
   *
   * @return int
   */
  public function getDefaultOrder() {
    return 900;
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
   * Check if this action is configured correctly
   *
   * @throws \Exception
   */
  public function checkConfiguration() {
    parent::checkConfiguration();
    $entity = $this->getConfigValue('php_code');
    if (empty($entity)) {
      throw new Exception('PHP code not provided', 1);
    }
  }

  /**
   * RUN this action
   *
   * @throws \Exception
   */
  public function execute() {
    // has_executed is always false for RunSQL
    $this->resetHasExecuted();
    try {
      eval(html_entity_decode($this->getConfigValue('php_code')));
    }
    catch (Exception $e) {
      $this->log("PHP failed: " . $e->getMessage() . " - " . $e->getTraceAsString());
      throw $e;
    }
  }

}
