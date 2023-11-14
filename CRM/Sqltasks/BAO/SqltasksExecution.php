<?php
// phpcs:disable
use CRM_Sqltasks_ExtensionUtil as E;
// phpcs:enable

class CRM_Sqltasks_BAO_SqltasksExecution extends CRM_Sqltasks_DAO_SqltasksExecution {

  private $log_entries = [];
  private $log_to_file = FALSE;
  private $return_values = [];
  private $start_timestamp = 0;

  static private $file_entries = [];

  public function __construct($params = []) {
    parent::__construct();

    unset($params['execution_id']);

    $this->log_to_file = $params['log_to_file'] ?? FALSE;
    unset($params['log_to_file']);

    $this->copyValues($params);
  }

  /**
   * Register a file this action has generated, and that's ready for download
   *
   * @param $title          string meaningful title
   * @param $filename       string file name
   * @param $path           string file path
   * @param $mime_type      string mime type
   * @param $download_link  boolean should the file be offered as a download link, in UI and as success mail token
   * @param $attachment     boolean should the file be attached to the success mail
   */
  public function addGeneratedFile(
    $title,
    $filename,
    $path,
    $mime_type,
    $download_link = TRUE,
    $attachment = FALSE
  ) {
    $config = CRM_Core_Config::singleton();
    $base_name = basename($path);
    $new_path = $config->customFileUploadDir . $base_name;
    copy($path, $new_path);

    $file = civicrm_api3('File', 'create', array(
        'description' => $title,
        'mime_type'   => $mime_type,
        'uri'         => $base_name,
    ));

    $download_link = CRM_Utils_System::url(
      "civicrm/file",
      "reset=1&id={$file['id']}&filename={$base_name}&mime-type={$mime_type}",
      TRUE
    );

    $file_entry = [
        'as_attachment' => $attachment,
        'download_link' => $download_link,
        'file_id'       => $file['id'],
        'filename'      => $filename,
        'mime_type'     => $mime_type,
        'offer_link'    => $download_link,
        'path'          => $path,
        'task_id'       => $this->getID(),
        'title'         => $title,
    ];

    self::$file_entries[] = $file_entry;

    $this->logInfo("Published file '$filename' with URL $download_link", 'info');
  }

  /**
   * Create a new SqltasksExecution based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Sqltasks_DAO_SqltasksExecution|NULL
   */
  public static function create($params) {
    CRM_Utils_Hook::pre('create', 'SqltasksExecution', NULL, $params);

    $params = [
      'created_id'  => CRM_Core_Session::getLoggedInContactID(),
      'error_count' => 0,
      'files'       => '[]',
      'input'       => $params['input'],
      'log'         => '[]',
      'log_to_file' => !empty($params['log_to_file']),
      'runtime'     => 0,
      'sqltask_id'  => $params['sqltask_id'],
      'start_date'  => CRM_Utils_Array::value('start_date', $params, date('Y-m-d H:i:s')),
    ];

    $instance = new self($params);
    $instance->save();

    self::$file_entries = [];

    CRM_Utils_Hook::post('create', 'SqltasksExecution', $instance->id, $instance);

    return $instance;
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

  public static function getAllFiles() {
    return self::$file_entries;
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

  public static function getLastFile() {
    return end(self::$file_entries);
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

  /**
   * Check if the task has encountered errors during execution
   */
  public function hasErrors() {
    return $this->error_count > 0;
  }

  public function logError($message) {
    return $this->logGeneric('error', $message);
  }

  public function logGeneric($type = 'info', $message) {
    $this->log_entries[] = [
      'message'                   => $message,
      'message_type'              => $type,
      'timestamp_in_microseconds' => microtime(TRUE),
    ];

    $this->log = json_encode($this->log_entries);
    $this->updateRuntime();
    $this->save();

    if ($this->log_to_file) {
      CRM_Core_Error::debug_log_message($this->renderLogMessage($message), FALSE, 'sqltasks');
    }
  }

  public function logInfo($message) {
    return $this->logGeneric('info', $message);
  }

  public function logWarning($message) {
    return $this->logGeneric('warning', $message);
  }

  /**
   * @param $logsString
   * @return array
   */
  public static function prepareLogs($logsString) {
    if (empty($logsString)) {
      return [];
    }

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

  private function renderLogMessage($log_entry) {
    $message = $log_entry['message'] ?? '';
    $type = $log_entry['message_type'] ?? '';

    return (!empty($type) ? "$type: " : '') . "[Task {$this->sqltask_id}] $message";
  }

  public function reportError($error_message = NULL) {
    $this->error_count++;

    if (isset($error_message)) {
      $this->logError($error_message);
    }
  }

  public function result() {
    return array_merge([
      'error_count' => $this->error_count,
      'logs'        => array_map(fn ($entry) => $this->renderLogMessage($entry), $this->log_entries),
      'runtime'     => sprintf("%.3fs", $this->runtime / 1000),
      'status'      => $this->error_count < 1 ? 'success' : 'error',
    ], $this->return_values);
  }

  public function setReturnValue($key, $value) {
    if (!empty($this->return_values[$key])) {
      $this->logWarning("Overwrite existing key '$key'");
    }

    $this->return_values[$key] = $value;
  }

  public function start() {
    $this->start_date = date('Y-m-d H:i:s');
    $this->start_timestamp = (int) (microtime(TRUE) * 1000);
    $this->save();
  }

  public function stop() {
    $this->updateRuntime();
    $this->end_date = date('Y-m-d H:i:s');
    $this->files = json_encode(self::$file_entries);
    $this->save();
  }

  private function updateRuntime() {
    $current_timestamp = (int) (microtime(TRUE) * 1000);
    $this->runtime = $current_timestamp - $this->start_timestamp;
  }

  /**
   * Write current log into a temp file
   */
  public function writeLogfile() {
    $logfile = tempnam(sys_get_temp_dir(), 'sqltask-') . '.log';

    if ($logfile) {
      $handle = fopen($logfile, 'w');

      foreach ($this->log_entries as $entry) {
        fwrite($handle, $this->renderLogMessage($entry) . "\r\n");
      }

      fclose($handle);
    }

    return $logfile;
  }

}
