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

    $sqltasksExecutions = CRM_Sqltasks_BAO_SqltasksExecution::getAll(['id' => $id]);

    foreach ($sqltasksExecutions as $sqltasksExecution) {
      return $sqltasksExecution;
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
      'decoded_logs' => CRM_Sqltasks_BAO_SqltasksExecution::prepareLogs($dao->log),
      'files' => $dao->files,
      'error_count' => $dao->error_count,
      'created_id' => $dao->created_id,
      'created_contact_display_name' => $dao->created_contact_display_name,
    ];
  }

  /**
   * @param $logsString
   * @return array
   */
  public static function prepareLogs($logsString) {
    $logs = json_decode($logsString, true);

    foreach ($logs as $key => $log) {
      if (!empty($log['timestamp_in_microseconds'])) {
        $dateTimeObj = DateTime::createFromFormat('U.u', $log['timestamp_in_microseconds']);
        if (!empty($dateTimeObj)) {
          $logs[$key]['date_time_obj'] = $dateTimeObj;
        } else {
          $logs[$key]['date_time_obj'] = (new DateTime())->setTimestamp(0);
        }
      } else {
        $logs[$key]['date_time_obj'] = (new DateTime())->setTimestamp(0);
      }
    }

    return $logs;
  }

  /**
   * Gets all data
   *
   * @param array $params
   *
   * @return array
   */
  public static function getAll($params = []) {
    //TODO: rewrite this method to use api4?
    $query = CRM_Sqltasks_BAO_SqltasksExecution::buildQuery($params);
    $dao = CRM_Core_DAO::executeQuery($query->toSQL());
    $items = [];

    while ($dao->fetch()) {
      $items[] = self::prepareData($dao);
    }

    return $items;
  }

  /**
   * @param $sqltaskId
   * @return int|null
   */
  public static function getTheLatestExecutionId($sqltaskId) {
    $sqltaskId = (int) $sqltaskId;
    if (empty($sqltaskId)) {
      return null;
    }

    $sqltasksExecutions = CRM_Sqltasks_BAO_SqltasksExecution::getAll(['sqltask_id' => $sqltaskId, 'order_by' => ['civicrm_sqltasks_execution.id' => 'DESC']]);

    foreach ($sqltasksExecutions as $sqltasksExecution) {
      return $sqltasksExecution['id'];
    }

    return null;
  }

  public static function buildQuery($params = []) {
    $query = CRM_Utils_SQL_Select::from(CRM_Sqltasks_BAO_SqltasksExecution::getTableName());

    $query->select('civicrm_sqltasks_execution.*');
    $query->join('civicrm_contact', "LEFT JOIN civicrm_contact AS civicrm_contact ON civicrm_sqltasks_execution.created_id = civicrm_contact.id");
    $query->select('civicrm_contact.display_name as created_contact_display_name');

    if (!empty($params['id'])) {
      $query->where('civicrm_sqltasks_execution.id = #id', ['id' => $params['id']]);
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

    if (!empty($params['order_by']) && is_array($params['order_by'])) {
      $availableFieldsToSort = ['civicrm_sqltasks_execution.id', '.civicrm_sqltasks_execution.end_date', 'civicrm_sqltasks_execution.start_date'];

      foreach ($params['order_by'] as $orderByColumn => $orderByType) {
        if (in_array($orderByColumn, $availableFieldsToSort) && in_array($orderByType, ['DESC', 'ASC'])) {
          $query->orderBy($orderByColumn . ' ' . $orderByType);
        }
      }
    }

    return $query;
  }

}
