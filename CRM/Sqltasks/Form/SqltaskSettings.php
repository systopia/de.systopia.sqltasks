<?php

use Civi\Utils\Sqltasks\Settings;
use CRM_Sqltasks_ExtensionUtil as E;

class CRM_Sqltasks_Form_SqltaskSettings extends CRM_Core_Form {

  public function buildQuickForm() {
    $this->setTitle(E::ts('SQL Task Settings'));
    $this->add('checkbox', Settings::SQLTASKS_IS_DISPATCHER_DISABLED, E::ts('SQL Task Dispatcher disabled?'));
    $this->add('number', Settings::SQLTASKS_MAX_FAILS_NUMBER, E::ts('Maximum number of fails before dispatcher is disabled'), null, TRUE);
    $this->addButtons([[
      'type' => 'submit',
      'name' => E::ts('Save'),
      'isDefault' => TRUE,
    ]]);

    $this->assign('settingsNames', [Settings::SQLTASKS_IS_DISPATCHER_DISABLED, Settings::SQLTASKS_MAX_FAILS_NUMBER]);
    $this->assign('SqltaskManagerLink', CRM_Utils_System::url('civicrm/a/', NULL, TRUE, "/sqltasks/manage"));

    CRM_Core_Resources::singleton()->addStyleFile('de.systopia.sqltasks', 'css/sqlTaskGeneral.css');
    CRM_Core_Resources::singleton()->addScriptFile('de.systopia.sqltasks', 'js/AddBodyClass.js', 1000, 'html-header');

    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    return [
        Settings::SQLTASKS_IS_DISPATCHER_DISABLED => Settings::isDispatcherDisabled(),
        Settings::SQLTASKS_MAX_FAILS_NUMBER => Settings::getMaxFailsNumber(),
    ];
  }

  public function postProcess() {
    $values = $this->exportValues();

    if (!empty($values[Settings::SQLTASKS_IS_DISPATCHER_DISABLED]) && $values[Settings::SQLTASKS_IS_DISPATCHER_DISABLED] == 1) {
      Settings::disableDispatcher();
    } else {
      Settings::enableDispatcher();
    }

    if (!empty($values[Settings::SQLTASKS_MAX_FAILS_NUMBER])) {
      Settings::setMaxFailsNumber($values[Settings::SQLTASKS_MAX_FAILS_NUMBER]);
    }

    CRM_Core_Session::setStatus(E::ts('Saved!'));
    parent::postProcess();
  }

}
