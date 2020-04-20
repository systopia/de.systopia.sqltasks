<?php

if (class_exists('CRM_Civirules_Action')) {

  /**
   * Class for CiviRules SQL Task Action
   */
  class CRM_CivirulesActions_SQLTask extends CRM_Civirules_Action {

    /**
     * Method to return the url for additional form processing for action
     * and return false if none is needed
     *
     * @param int $ruleActionId
     *
     * @return bool
     * @access public
     */
    public function getExtraDataInputUrl($ruleActionId) {
      return CRM_Utils_System::url('civicrm/civirule/form/action/sqltask', 'rule_action_id=' . $ruleActionId);
    }

    /**
     * Method processAction to execute the action
     *
     * @param CRM_Civirules_TriggerData_TriggerData $triggerData
     *
     * @access public
     *
     */
    public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData) {
      $data = [
        'trigger' => get_class($triggerData->getTrigger()),
        'contact_id' => $triggerData->getContactId(),
        'entity_custom_data' => $triggerData->getEntityCustomData(),
      ];

      if ($triggerData instanceof CRM_Civirules_TriggerData_Post
        || $triggerData instanceof CRM_Civirules_TriggerData_Cron) {
        $entity = strtolower($triggerData->getEntity());
        $data['entity'] = $entity;
        $data["{$entity}_data"] = $triggerData->getEntityData($data['entity']);
      }

      if ($triggerData instanceof CRM_Civirules_TriggerData_Interface_OriginalData) {
        $data['original_data'] = $triggerData->getOriginalData();
      }

      $json = json_encode($data);

      $params = $this->getActionParameters();

      try {
        civicrm_api3('Sqltask', 'execute', [
          'task_id' => $params['sqltask_id'],
          'input_val' => $json,
          'log_to_file' => !empty($params['log_to_file']),
          'check_permissions' => FALSE,
        ]);
      } catch (CiviCRM_API3_Exception $e) {}
    }

    /**
     * @return string
     * @access public
     */
    public function userFriendlyConditionParams() {
      $params = $this->getActionParameters();
      if (!empty($params['sqltask_id'])) {
        return CRM_Sqltasks_Task::getTask($params['sqltask_id'])
          ->getAttribute('name');
      }

      return '';
    }
  }
}
else {
  throw new Exception('Class "CRM_Civirules_Action" does not exists. Please install "CiviRules" extension.', 1);
}
