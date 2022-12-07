<?php
// phpcs:disable
use CRM_Sqltasks_ExtensionUtil as E;
// phpcs:enable

class CRM_Sqltasks_BAO_SqltasksExecution extends CRM_Sqltasks_DAO_SqltasksExecution {

  /**
   * Create a new SqltasksExecution based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Sqltasks_DAO_SqltasksExecution|NULL
   */
  /*
  public static function create($params) {
    $className = 'CRM_Sqltasks_DAO_SqltasksExecution';
    $entityName = 'SqltasksExecution';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }
  */

}
