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
 * Run SQL at the end of a task
 *
 */
class CRM_Sqltasks_Action_PostSQL extends CRM_Sqltasks_Action_RunSQL {

  /**
   * Get identifier string
   */
  public function getID() {
    return 'post_sql';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('Run Cleanup SQL Script');
  }

  /**
   * Get default template order
   *
   * @return int
   */
  public function getDefaultOrder() {
    return 8000;
  }

}
