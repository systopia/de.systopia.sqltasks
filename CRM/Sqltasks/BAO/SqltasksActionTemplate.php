<?php
use CRM_Sqltasks_ExtensionUtil as E;

class CRM_Sqltasks_BAO_SqltasksActionTemplate extends CRM_Sqltasks_DAO_SqltasksActionTemplate {

  /**
   * Create a new SqltasksActionTemplate based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Sqltasks_DAO_SqltasksActionTemplate|NULL
   *
  public static function create($params) {
    $className = 'CRM_Sqltasks_DAO_SqltasksActionTemplate';
    $entityName = 'SqltasksActionTemplate';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
