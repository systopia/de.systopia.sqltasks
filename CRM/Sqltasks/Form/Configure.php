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

/**
 * Redirect to AngularJS UI
 */
class CRM_Sqltasks_Form_Configure extends CRM_Core_Form {

  /**
   * Compile config form
   */
  public function buildQuickForm() {
    // get the ID
    $task_id = CRM_Utils_Request::retrieve('tid', 'Integer');
    $redirectUrl = CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/a/', NULL, TRUE, "/sqltasks/configure/{$task_id}"));
    CRM_Utils_System::redirect($redirectUrl);
  }

}
