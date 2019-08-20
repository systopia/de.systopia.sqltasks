<?php

use CRM_Sqltasks_ExtensionUtil as E;

if (class_exists('CRM_CivirulesActions_Form_Form')) {

  /**
   * Class for CiviRules SQL Task Action Form
   */
  class CRM_CivirulesActions_Form_SQLTask extends CRM_CivirulesActions_Form_Form {

    /**
     * Method to get SQL Tasks
     *
     * @return array
     * @access protected
     */
    protected function getSQLTasks() {
      $sqlTasks = CRM_Sqltasks_Task::getExecutionTaskList();
      $options = [];

      foreach ($sqlTasks as $id => $sqlTask) {
        $options[$sqlTask->getID()] = $sqlTask->getAttribute('name');
      }

      return $options;
    }

    /**
     * Overridden parent method to build the form
     *
     * @access public
     */
    public function buildQuickForm() {
      $this->add('hidden', 'rule_action_id');
      $this->add(
        'select',
        'sqltask_id',
        E::ts('SQL Task'),
        ['' => E::ts('-- please select --')] + $this->getSQLTasks(),
        TRUE
      );
      $this->addButtons([
        ['type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE],
        ['type' => 'cancel', 'name' => ts('Cancel')],
      ]);
    }

    /**
     * Overridden parent method to process form data after submitting
     *
     * @access public
     */
    public function postProcess() {
      $this->ruleAction->action_params = serialize(['sqltask_id' => $this->_submitValues['sqltask_id']]);
      $this->ruleAction->save();

      parent::postProcess();
    }
  }
}
else {
  throw new Exception('Class "CRM_CivirulesActions_Form_Form" does not exists. Please install "CiviRules" extension.', 1);
}
