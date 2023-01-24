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

  public static function buildApiQuery($params) {
    $api = \Civi\Api4\SqltasksExecution::get();

    $fields = ['id', 'sqltask_id', 'created_id', 'input', 'error_count', 'start_date', 'end_date', 'runtime'];

    foreach ($params as $name => $value) {
      if (in_array($name, $fields)) {
        $api->addWhere($name, '=', $value);
      }
    }

    if (!empty($params['error_status']) && $params['error_status'] == 'only_errors') {
      $api->addWhere('error_count', '>', 0);
    }

    if (!empty($params['error_status']) && $params['error_status'] == 'no_errors') {
      $api->addWhere('error_count', '=', 0);
    }

    if (!empty($params['to_start_date'])) {
      $api->addWhere('start_date', '<=', $params["to_start_date"]);
    }

    if (!empty($params['from_start_date'])) {
      $api->addWhere('start_date', '>=', $params["from_start_date"]);
    }

    if (!empty($params['to_end_date'])) {
      $api->addWhere('end_date', '<=', $params["to_end_date"]);
    }

    if (!empty($params['from_end_date'])) {
      $api->addWhere('end_date', '>=', $params["from_end_date"]);
    }

    if (!empty($params['order_by']) && is_array($params['order_by'])) {
      foreach ($params['order_by'] as $orderByColumn => $orderByType) {
        if (in_array($orderByColumn, $fields) && in_array($orderByType, ['DESC', 'ASC'])) {
          $api->addOrderBy($orderByColumn, $orderByType);
        }
      }
    }

    if (!empty($params['limit_per_page']) && !empty($params['page_number'])) {
      $limit = (int) $params['limit_per_page'];
      $offset = (int) (($params['page_number'] - 1) * $limit);
      $api->setLimit($limit);
      $api->setOffset($offset);
    }

    if (!empty($params['limit'])) {
      $api->setLimit($params['limit']);
    }

    if (!empty($params['offset'])) {
      $api->setOffset($params['offset']);
    }

    return $api;
  }

  /**
   * Gets all data
   *
   * @param array $params
   *
   * @return array
   */
  public static function getAll($params = []) {
    $api = CRM_Sqltasks_BAO_SqltasksExecution::buildApiQuery($params);
    $api->addSelect('contact.display_name');
    $api->addSelect('*');
    $api->addJoin('Contact AS contact', 'LEFT', ['created_id', '=', 'contact.id']);

    $sqltasksExecutions = $api->execute();
    $items = [];

    foreach ($sqltasksExecutions as $sqltasksExecution) {
      $items[] = [
        'id' => $sqltasksExecution['id'],
        'sqltask_id' => $sqltasksExecution['sqltask_id'],
        'is_has_errors' => $sqltasksExecution['error_count'] > 0,
        'start_date' => $sqltasksExecution['start_date'],
        'end_date' => $sqltasksExecution['end_date'],
        'runtime' => $sqltasksExecution['runtime'],
        'input' => $sqltasksExecution['input'],
        'log' => $sqltasksExecution['log'],
        'decoded_logs' => CRM_Sqltasks_BAO_SqltasksExecution::prepareLogs($sqltasksExecution['log']),
        'files' => $sqltasksExecution['files'],
        'error_count' => $sqltasksExecution['error_count'],
        'created_id' => $sqltasksExecution['created_id'],
        'created_contact_display_name' => $sqltasksExecution['contact.display_name'],
      ];
    }

    return $items;
  }

  public static function getSummary($params = []) {
    unset($params['page_number']);
    unset($params['limit_per_page']);
    unset($params['limit']);
    unset($params['offset']);

    $api = CRM_Sqltasks_BAO_SqltasksExecution::buildApiQuery($params);
    $api->setSelect([
      'COUNT(id) AS count',
      'AVG(runtime) AS avg',
      'MIN(runtime) AS min',
      'MAX(runtime) AS max',
    ]);

    return $api->execute()->first();
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

    $sqltasksExecutions = CRM_Sqltasks_BAO_SqltasksExecution::getAll(['sqltask_id' => $sqltaskId, 'order_by' => ['id' => 'DESC'], 'limit' => 1]);

    foreach ($sqltasksExecutions as $sqltasksExecution) {
      return $sqltasksExecution['id'];
    }

    return null;
  }

}
