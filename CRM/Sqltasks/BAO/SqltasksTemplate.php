<?php

use CRM_Sqltasks_ExtensionUtil as E;

class CRM_Sqltasks_BAO_SqltasksTemplate extends CRM_Sqltasks_DAO_SqltasksTemplate {

  /**
   * Create a new template
   *
   * @param array $params
   *
   * @return CRM_Sqltasks_DAO_SqltasksTemplate|null
   */
  public static function create ($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, "SqltasksTemplate", CRM_Utils_Array::value('id', $params), $params);
    $instance = new CRM_Sqltasks_BAO_SqltasksTemplate();
    $instance->copyValues($params);
    $instance->last_modified = date("Y-m-d H:i:s");
    $instance->save();
    CRM_Utils_Hook::post($hook, "SqltasksTemplate", $instance->id, $instance);

    return $instance;
  }

  /**
   * Delete a template with a given ID
   *
   * @param bool $id
   */
  public static function deleteOne ($id) {
    $params = [ "id" => $id ];

    CRM_Utils_Hook::pre("delete", "SqltasksTemplate", $id, $params);
    $instance = new CRM_Sqltasks_DAO_SqltasksTemplate();
    $instance->id = $id;
    $instance->delete();
    CRM_Utils_Hook::post("delete", "SqltasksTemplate", $id, $params);
  }

  /**
   * Instantiate from paramters
   *
   * @param CRM_Sqltasks_DAO_SqltasksTemplate $dao
   *
   * @return CRM_Sqltasks_BAO_SqltasksTemplate
   */
  public static function fromDAO ($dao) {
    $instance = new CRM_Sqltasks_BAO_SqltasksTemplate();

    foreach (CRM_Sqltasks_DAO_SqltasksTemplate::fieldKeys() as $prop) {
      $instance->$prop = $dao->$prop;
    }

    return $instance;
  }

  /**
   * Get a list of all templates in the database
   *
   * @return array
   */
  public static function getAll () {
    $result = [];

    $dao = CRM_Core_DAO::executeQuery(
      "SELECT * FROM %1;",
      [ 1 => [ self::getTableName(), "MysqlColumnNameOrAlias" ] ]
    );

    while ($dao->fetch()) {
      array_push($result, CRM_Sqltasks_BAO_SqltasksTemplate::fromDAO($dao));
    }

    return $result;
  }

  /**
   * Get a template by ID from the database
   *
   * @param string $id
   *
   * @return CRM_Sqltasks_BAO_SqltasksTemplate|null
   */
  public static function getOne ($id) {
    $result = [];

    $dao = CRM_Core_DAO::executeQuery(
      "SELECT * FROM %1 WHERE `id` = %2;",
      [
        1 => [ self::getTableName(), "MysqlColumnNameOrAlias" ],
        2 => [ $id, "Integer" ],
      ]
    );

    $dao->fetch();

    if (empty($dao->id)) return null;

    return CRM_Sqltasks_BAO_SqltasksTemplate::fromDAO($dao);
  }

  /**
   * Map instance to array
   *
   * @return array
   */
  public function mapToArray () {
    $result = [];

    foreach (CRM_Sqltasks_DAO_SqltasksTemplate::fieldKeys() as $prop) {
      $result[$prop] = $this->$prop;
    }

    return $result;
  }

}

?>
