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

  /**
   * @param $daoItems
   * @return array
   */
  public static function decodeLogs($daoItems) {
    $decodedItems = [];

    foreach ($daoItems as $item) {
      $item['logs'] = json_decode($item['log']);
      $decodedItems[] = $item;
    }

    return $decodedItems;
  }

  /**
   * @param $id
   * @return array|null
   */
  public static function getById($id) {
    if (empty($id)) {
      return NULL;
    }

    $dao = CRM_Core_DAO::executeQuery("SELECT * FROM %1 WHERE id = %2;", [
      1 => [self::getTableName(), "MysqlColumnNameOrAlias"],
      2 => [$id, "Integer"],
    ]);

    while ($dao->fetch()) {
      return self::prepareData($dao);
    }

    return NULL;
  }

  /**
   * @param $dao
   * @return array
   */
  protected static function prepareData($dao) {
    return [
      'id' => $dao->id,
      'sqltask_id' => $dao->sqltask_id,
      'is_has_errors' => $dao->error_count > 0,
      'start_date' => $dao->start_date,
      'end_date' => $dao->end_date,
      'runtime' => $dao->runtime,
      'input' => $dao->input,
      'log' => $dao->log,
      'decoded_logs' => json_decode($dao->log, true),
      'files' => $dao->files,
      'error_count' => $dao->error_count,
      'created_id' => $dao->created_id,
    ];
  }

  /**
   * Builds query for receiving data
   *
   * @param string $returnValueType
   *
   * @return \CRM_Utils_SQL_Select
   */
  private static function buildSelectQuery($returnValueType = null) {
    $query = CRM_Utils_SQL_Select::from(CRM_Sqltasks_BAO_SqltasksExecution::getTableName());

    if ($returnValueType == 'count') {
      $query->select('COUNT(id)');
    } else {
      $query->select('*');
    }

    return $query;
  }

  /**
   * Builds 'where' condition for query
   *
   * @param $query
   * @param array $params
   *
   * @return mixed
   */
  private static function buildWhereQuery($query, $params = []) {
    if (!empty($params['id'])) {
      $query->where('id = #id', ['id' => $params['id']]);
    }

    if (!empty($params['sqltask_id'])) {
      $query->where('sqltask_id = #sqltask_id', ['sqltask_id' => $params['sqltask_id']]);
    }

    if (!empty($params['created_id'])) {
      $query->where('created_id = #created_id', ['created_id' => $params['created_id']]);
    }

    if (!empty($params['input'])) {
      $query->where('input = @input', ['input' => $params['input']]);
    }

    if (!empty($params['error_count'])) {
      $query->where('error_count = #error_count', ['error_count' => $params['error_count']]);
    }

    if (!empty($params['is_has_errors'])) {
      $query->where('error_count > 0');
    }

    if (!empty($params['is_has_no_errors'])) {
      $query->where('error_count = 0');
    }

    if (!empty($params['start_date'])) {
      $query->where('start_date = @start_date', ['start_date' => $params["start_date"]]);
    }

    if (!empty($params['to_start_date'])) {
      $query->where('start_date <= @to_start_date', ['to_start_date' => $params["to_start_date"]]);
    }

    if (!empty($params['from_start_date'])) {
      $query->where('start_date >= @from_start_date', ['from_start_date' => $params["from_start_date"]]);
    }

    if (!empty($params['to_end_date'])) {
      $query->where('end_date <= @to_end_date', ['to_end_date' => $params["to_end_date"]]);
    }

    if (!empty($params['from_end_date'])) {
      $query->where('end_date >= @from_end_date', ['from_end_date' => $params["from_end_date"]]);
    }

    if (!empty($params['end_date'])) {
      $query->where('end_date = @end_date', ['end_date' => $params["end_date"]]);
    }

    return $query;
  }

  /**
   * Adds order params to query
   *
   * @param $query
   * @param array $params
   * @return mixed
   */
  private static function buildOrderQuery($query, $params = []) {
    if (!empty($params['options']['sort'])) {
      $sortParams = explode(' ', strtolower($params['options']['sort']));
      $availableFieldsToSort = ['id', 'end_date', 'start_date'];
      $order = '';

      if (!empty($sortParams[1]) && ($sortParams[1] == 'desc' || $sortParams[1] == 'asc')) {
        $order = $sortParams[1];
      }

      if (in_array($sortParams[0], $availableFieldsToSort)) {
        $query->orderBy($sortParams[0] . ' ' . $order);
      }
    }

    return $query;
  }

  /**
   * Gets all data
   *
   * @param array $params
   *
   * @return array
   */
  public static function getAll($params = []) {
    $query = self::buildOrderQuery(self::buildWhereQuery(self::buildSelectQuery(), $params), $params);
    $dao = CRM_Core_DAO::executeQuery($query->toSQL());
    $items = [];

    while ($dao->fetch()) {
      $items[] = self::prepareData($dao);
    }

    return $items;
  }

}
