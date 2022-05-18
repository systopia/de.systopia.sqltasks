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

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * This is a generic handler to communicate the completion of
 * a SQL task. Currently there are two handlers in use:
 *  'success' will be triggered when the task was completed successfully
 *  'error'   will be triggered if an error occurs during execution
 *
 */
class CRM_Sqltasks_Action_SuccessHandler extends CRM_Sqltasks_Action_ResultHandler {

  /**
   * Get default template order
   *
   * @return int
   */
  public function getDefaultOrder() {
    return 9000;
  }

  public function __construct(CRM_Sqltasks_Task $task, array $config) {
    parent::__construct($task, $config, 'success', E::ts('Success Handler'));
  }

}
