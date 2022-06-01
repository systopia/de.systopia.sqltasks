<?php

use CRM_Sqltasks_ExtensionUtil as E;

class CRM_Sqltasks_Page_GlobalTokenManager extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts("SQL Task Global Token Manager"));

    $this->assign('tokens', json_encode((CRM_Sqltasks_GlobalToken::singleton())->getAllTokenData()));
    $this->assign('maxLengthOfTokenName', CRM_Sqltasks_GlobalToken::MAX_LENGTH_OF_TOKEN_NAME);

    parent::run();
  }

}
