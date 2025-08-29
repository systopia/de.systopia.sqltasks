<?php

use Civi\Api4;
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
      $sqlTasks = Api4\SqlTask::get()
        ->addSelect('id', 'name')
        ->addOrderBy('weight', 'ASC')
        ->addOrderBy('id', 'ASC')
        ->execute();

      $options = [];

      foreach ($sqlTasks as $sqlTask) {
        $options[$sqlTask['id']] = '[' . $sqlTask['id'] . '] ' . $sqlTask['name'];
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
        ['' => E::ts('- select -')] + $this->getSQLTasks(),
        TRUE,
        ['class' => 'crm-select2 huge']
      );
      $this->add(
        'checkbox',
        'log_to_file',
        E::ts('Log task results?')
      );
      $this->addButtons([
        ['type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE],
        ['type' => 'cancel', 'name' => ts('Cancel')],
      ]);
    }

    /**
     * Overridden parent method to set default values
     *
     * @return array $defaultValues
     * @access public
     */
    public function setDefaultValues() {
      $defaultValues = parent::setDefaultValues();

      $data = unserialize($this->ruleAction->action_params);

      if (!empty($data['sqltask_id'])) {
        $defaultValues['sqltask_id'] = $data['sqltask_id'];
      }

      if (!empty($data['log_to_file'])) {
        $defaultValues['log_to_file'] = $data['log_to_file'];
      }

      return $defaultValues;
    }

    /**
     * Overridden parent method to process form data after submitting
     *
     * @access public
     */
    public function postProcess() {
      $this->ruleAction->action_params = serialize([
        'sqltask_id' => $this->_submitValues['sqltask_id'],
        'log_to_file' => !empty($this->_submitValues['log_to_file']),
      ]);
      $this->ruleAction->save();

      parent::postProcess();
    }
  }
}
else {
  throw new Exception('Class "CRM_CivirulesActions_Form_Form" does not exists. Please install "CiviRules" extension.', 1);
}
