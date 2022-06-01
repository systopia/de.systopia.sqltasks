<?php

use CRM_Sqltasks_ExtensionUtil as E;

class CRM_Sqltasks_Page_Templates extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts("SQL Task Configuration Templates"));

    $this->assign("templates", array_map(
      function ($bao) { return $bao->mapToArray(); },
      CRM_Sqltasks_BAO_SqltasksTemplate::getAll()
    ));

    $this->assign("defaultTemplateId", Civi::settings()->get("sqltasks_default_template"));

    parent::run();
  }

}
