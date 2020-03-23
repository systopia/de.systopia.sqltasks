<?php
/*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * This class represents a single task
 *
 * @todo turn this into an entity
 */
class CRM_Sqltasks_Task {

  protected static $main_attributes = [
    'name'            => 'String',
    'description'     => 'String',
    'category'        => 'String',
    'scheduled'       => 'String',
    'enabled'         => 'Integer',
    'weight'          => 'Integer',
    'last_execution'  => 'Date',
    'last_runtime'    => 'Integer',
    'parallel_exec'   => 'Integer',
    'run_permissions' => 'String',
    // REMOVED - DO NOT USE
    'main_sql'        => 'String',
    // REMOVED - DO NOT USE
    'post_sql'        => 'String',
    'input_required'  => 'Integer',
  ];

  protected $task_id;
  protected $attributes;
  protected $config;
  protected $status;
  protected $error_count;
  protected $log_messages;
  protected $log_to_file = FALSE;

  /** @var array generated, sregistered files */
  protected static $files = [];

  /**
   * Constructor
   */
  public function __construct($task_id, $data = []) {
    $this->task_id      = $task_id;
    $this->attributes   = [];
    $this->config       = [];
    $this->log_messages = [];
    $this->status       = 'init';
    $this->error_count  = 0;

    // main attributes go into $this->attributes
    foreach (self::$main_attributes as $attribute_name => $attribute_type) {
      $this->attributes[$attribute_name] = CRM_Utils_Array::value($attribute_name, $data);
    }
    $this->setDefaultAttributes();

    // everything else is passed to setConfiguration()
    $config = [];
    foreach ($data as $attribute_name => $value) {
      if (!isset(self::$main_attributes[$attribute_name])) {
        $config[$attribute_name] = $value;
      }
    }
    $this->setConfiguration($config);
  }

  /**
   * Set default values for some attributes
   */
  private function setDefaultAttributes() {
    $defaults = [
      'parallel_exec'  => 0,
      'input_required' => 0,
    ];
    foreach ($defaults as $attribute => $value) {
      if (empty($this->attributes[$attribute])) {
        $this->attributes[$attribute] = $value;
      }
    }
  }

  /**
   * get a single attribute from the task
   */
  public function getID() {
    return $this->task_id;
  }

  /**
   * get the current status of this task
   *
   * Should return one of:
   *  'init'    - task object has not yet been executed
   *  'running' - task object is curreently being executed
   *  'success' - task has been executed successfully
   *  'error'   - task has report one or more errors during execution
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * check if the task has encountered errors during execution
   */
  public function hasExecutionErrors() {
    return $this->error_count > 0 || $this->status == 'error';
  }

  /**
   * get configuration
   */
  public function getConfiguration() {
    return $this->config;
  }

  /**
   * set entire configuration
   *
   * @param $config
   * @param bool $writeTrough
   *
   * @return mixed
   */
  public function setConfiguration($config, $writeTrough = FALSE) {
    $config['version'] = CRM_Sqltasks_Config_Format::getVersion($config);
    if ($writeTrough && $this->task_id) {
      CRM_Core_DAO::executeQuery(
        "UPDATE `civicrm_sqltasks`
         SET `config` = %1
         WHERE id = %2",
        [
          1 => [json_encode($config), 'String'],
          2 => [$this->task_id, 'Integer'],
        ]
      );
    }
    return $this->config = $config;
  }

  /**
   * append log messages
   */
  public function log($message) {
    $message = "[Task {$this->getID()}] {$message}";
    $this->log_messages[] = $message;
    if ($this->log_to_file) {
      CRM_Core_Error::debug_log_message($message, FALSE, 'sqltasks');
    }
  }

  /**
   * clear log and files
   */
  public function reset() {
    $this->log_messages = [];
    self::$files = [];
  }

  /**
   * write current log into a temp file
   */
  public function writeLogfile() {
    $logfile = tempnam(sys_get_temp_dir(), 'sqltask-') . '.log';
    if ($logfile) {
      $handle = fopen($logfile, 'w');
      foreach ($this->log_messages as $message) {
        // fwrite($handle, mb_convert_encoding($message . "\n", 'utf8'));
        fwrite($handle, $message . "\r\n");
      }
      fclose($handle);
    }
    return $logfile;
  }

  /**
   * get a single attribute from the task
   */
  public function getAttribute($attribute_name) {
    return CRM_Utils_Array::value($attribute_name, $this->attributes);
  }

  /**
   * set a single attribute
   */
  public function setAttribute($attribute_name, $value, $writeTrough = FALSE) {
    if (isset(self::$main_attributes[$attribute_name])) {
      $this->attributes[$attribute_name] = $value;
      $this->setDefaultAttributes();
      if ($writeTrough && $this->task_id) {
        CRM_Core_DAO::executeQuery("UPDATE `civicrm_sqltasks`
                                    SET `{$attribute_name}` = %1
                                    WHERE id = {$this->task_id}",
                                    array(1 => array($value, self::$main_attributes[$attribute_name])));
      }
    } else {
      throw new Exception("Attribute '{$attribute_name}' unknown", 1);
    }
  }

  /**
   * Get all task attributes
   *
   * @return array
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * Store this task (create or update)
   */
  public function store() {
    $this->setDefaultAttributes();
    // sort out parameters
    $params = array();
    $fields = array();
    $index  = 1;
    foreach (self::$main_attributes as $attribute_name => $attribute_type) {
      if (  $attribute_name == 'last_execution'
         || $attribute_name == 'last_runtime') {
        // don't overwrite timestamp
        continue;
      }
      $value = $this->getAttribute($attribute_name);
      if ($value === NULL || $value === '') {
        $fields[$attribute_name] = "NULL";
      } else {
        $fields[$attribute_name] = "%{$index}";
        if (is_bool($value)) {
          // need to convert bools to int for DAO
          $value = (int) $value;
        }
        $params[$index] = array($value, $attribute_type);
        $index += 1;
      }
    }
    $fields['config'] = "%{$index}";
    $params[$index] = array(json_encode($this->config), 'String');

    // generate SQL
    if ($this->task_id) {
      $field_assignments = array();
      foreach ($fields as $key => $value) {
        $field_assignments[] = "`{$key}` = {$value}";
      }
      $field_assignment_sql = implode(', ', $field_assignments);
      $sql = "UPDATE `civicrm_sqltasks` SET {$field_assignment_sql} WHERE id = {$this->task_id}";
    } else {
      $columns = array();
      $values  = array();
      foreach ($fields as $key => $value) {
        $columns[] = $key;
        $values[]  = $value;
      }
      $columns_sql = implode(',', $columns);
      $values_sql  = implode(',', $values);
      $sql = "INSERT INTO `civicrm_sqltasks` ({$columns_sql}) VALUES ({$values_sql});";
    }
    CRM_Core_DAO::executeQuery($sql, $params);
    if (empty($this->task_id)) {
      $this->task_id = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
    }
  }



  /**
   * Executes the given task
   */
  public function execute($params = []) {
    if (!empty($params['log_to_file'])) {
      $this->log_to_file = TRUE;
    }
    $this->status = 'running';
    $this->error_count = 0;
    $this->reset();
    $task_timestamp = microtime(TRUE) * 1000;

    // 0. mark task as started
    $is_still_running = CRM_Core_DAO::singleValueQuery("SELECT running_since FROM `civicrm_sqltasks` WHERE id = {$this->task_id};");
    if ($is_still_running) {
      $this->status = 'error';
      $this->log("Task is still running. Execution skipped.");
      return $this->log_messages;
    } else {
      // set last_execution and running_since
      CRM_Core_DAO::executeQuery("UPDATE `civicrm_sqltasks` SET last_execution = NOW(), running_since = NOW() WHERE id = {$this->task_id};");
    }

    $actions = CRM_Sqltasks_Action::getAllActiveActions($this);
    $context = [
      'actions' => $actions,
      'random'  => CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC),
    ];
    if ($this->getAttribute('input_required') && !empty($params['input_val'])) {
      $context['input_val'] = $params['input_val'];
    }
    foreach ($actions as $action) {
      $action_name = $action->getName();
      $timestamp = microtime(TRUE);
      $action->setContext($context);

      // check action configuration
      try {
        $action->checkConfiguration();
      } catch (Exception $e) {
        $this->error_count += 1;
        $this->log("Configuration Error '{$action_name}': " . $e -> getMessage());
        continue;
      }

      // run action
      try {
        $action->execute();
        $runtime = sprintf("%.3f", (microtime(TRUE) - $timestamp));
        $this->log("Action '{$action_name}' executed in {$runtime}s.");
      } catch (Exception $e) {
        $this->error_count += 1;
        $this->log("Error in action '{$action_name}': " . $e -> getMessage());
      }
    }

    $task_runtime = (int) (microtime(TRUE) * 1000) - $task_timestamp;
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_sqltasks` SET running_since = NULL, last_runtime = {$task_runtime} WHERE id = {$this->task_id};");
    if ($this->error_count) {
      $this->status = 'error';
    } else {
      $this->status = 'success';
    }

    return $this->log_messages;
  }


  /**
   * execute a single SQL script
   */
  protected function executeSQLScript($script, $script_name) {
    if (empty($script)) {
      $this->log("No '{$script_name}' given.");
      return;
    }

    $timestamp = microtime(TRUE);
    try {
      // prepare
      $config = CRM_Core_Config::singleton();
      $script = html_entity_decode($script);

      // run the whole script (see CRM-20428 and
      //   https://github.com/systopia/de.systopia.sqltasks/issues/2)
      if (version_compare(CRM_Utils_System::version(), '4.7.20', '<')) {
        CRM_Utils_File::sourceSQLFile($config->dsn, $script, NULL, TRUE);
      } else {
        CRM_Utils_File::runSqlQuery($config->dsn, $script);
      }

      $runtime = sprintf("%.3f", (microtime(TRUE) - $timestamp));
      $this->log("Script '{$script_name}' executed in {$runtime}s.");
    } catch (Exception $e) {
      $this->error_count += 1;
      $message = $e->getMessage();
      if ($e instanceof PEAR_Exception && $e->getCause() instanceof DB_Error) {
        $message .= ' Details: ' . $e->getCause()->getUserInfo();
      }
      $this->log("Script '{$script_name}' failed: " . $message);
    }
  }

  /**
   * delete a task with the given ID
   */
  public static function delete($tid) {
    $tid = (int) $tid;
    if (empty($tid)) return NULL;
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_sqltasks WHERE id = {$tid}");
  }

  /**
   * Get a list of all tasks
   *
   * @return CRM_Sqltasks_Task[]
   */
  public static function getAllTasks() {
    return self::getTasks('SELECT * FROM civicrm_sqltasks ORDER BY weight ASC, id ASC');
  }

  /**
   * Get a list of tasks ready for execution
   */
  public static function getExecutionTaskList() {
    return self::getTasks('SELECT * FROM civicrm_sqltasks WHERE enabled=1 ORDER BY weight ASC, id ASC');
  }

  /**
   * Get a list of tasks ready for execution
   */
  public static function getParallelExecutionTaskList() {
    return self::getTasks('SELECT * FROM civicrm_sqltasks WHERE enabled=1 AND parallel_exec = 1 ORDER BY weight ASC, id ASC');
  }

  /**
   * Get a list of all SQL Task categories
   */
  public static function getTaskCategoryList() {
    $categories = [];
    $categoryDAO = CRM_Core_DAO::executeQuery("SELECT DISTINCT(category) AS category FROM `civicrm_sqltasks`;");

    while ($categoryDAO->fetch()) {
      $categories[] = $categoryDAO->category;
    }

    return $categories;
  }

  /**
   * Load a list of tasks based on the data yielded by the given SQL query
   *
   * @return CRM_Sqltasks_Task[]
   */
  public static function getTasks($sql_query) {
    $tasks = array();
    $task_search = CRM_Core_DAO::executeQuery($sql_query);
    while ($task_search->fetch()) {
      $data = array();
      foreach (self::$main_attributes as $attribute_name => $attribute_type) {
        $data[$attribute_name] = $task_search->$attribute_name;
      }
      if (isset($task_search->config)) {
        $config = json_decode($task_search->config, TRUE);
        foreach ($config as $key => $value) {
          $data[$key] = $value;
        }
      }
      $tasks[] = new CRM_Sqltasks_Task($task_search->id, $data);
    }

    return $tasks;
  }

  /**
   * Load a list of tasks based on the data yielded by the given SQL query
   *
   * @return CRM_Sqltasks_Task task
   */
  public static function getTask($tid) {
    $tid = (int) $tid;
    if (empty($tid)) return NULL;
    $tasks = self::getTasks("SELECT * FROM `civicrm_sqltasks` WHERE id = {$tid}");
    return reset($tasks);
  }

  /**
   * Export task configuration
   */
  public function exportConfiguration() {
    // copy the attributes
    $config = $this->attributes;
    unset($config['name']);
    unset($config['enabled']);
    unset($config['weight']);
    unset($config['last_execution']);
    unset($config['last_runtime']);
    $config['config'] = $this->config;
    return json_encode($config, JSON_PRETTY_PRINT);
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
  public function addGeneratedFile($title, $filename, $path, $mime_type, $download_link = TRUE, $attachment = FALSE) {
    // create the file object
    $config = CRM_Core_Config::singleton();
    $base_name = basename($path);
    $newPath = $config->customFileUploadDir . $base_name;
    copy($path, $newPath);
    $file = civicrm_api3('File', 'create', array(
        'uri'           => $base_name,
        'mime_type'     => $mime_type,
        'description'   => $title,
    ));

    // add file entry
    $file_entry = [
        'title'         => $title,
        'filename'      => $filename,
        'path'          => $path,
        'mime_type'     => $mime_type,
        'task_id'       => $this->getID(),
        'offer_link'    => $download_link,
        'as_attachment' => $attachment,
        'file_id'       => $file['id'],
        'download_link' => CRM_Utils_System::url("civicrm/file", "reset=1&id={$file['id']}&filename={$base_name}&mime-type={$mime_type}", TRUE),
    ];
    self::$files[] = $file_entry;
    $this->log("Published file '$filename' with URL {$file_entry['download_link']}");
  }



  //  +---------------------------------+
  //  |       Scheduling Logic          |
  //  +---------------------------------+

  /**
   * main dispatcher, triggered by a scheduled Job
   */
  public static function runDispatcher($params = []) {
    $results = array();

    // FIRST reset timed out tasks (after 23 hours)
    CRM_Core_DAO::executeQuery("
      UPDATE `civicrm_sqltasks`
         SET running_since = NULL
       WHERE running_since < (NOW() - INTERVAL 23 HOUR);");

    // THEN: find out if still running
    $still_running = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*)
        FROM `civicrm_sqltasks`
       WHERE running_since IS NOT NULL;");

    if (!$still_running) {
      // NORMAL DISPATCH
      $tasks = CRM_Sqltasks_Task::getExecutionTaskList();
      foreach ($tasks as $task) {
        if ($task->allowedToRun() && $task->shouldRun()) {
          $results[] = $task->execute($params);
        }
      }

    } else {
      // PARALLEL DISPATCH: only run tasks flagged as parallel
      $tasks = CRM_Sqltasks_Task::getParallelExecutionTaskList();
      foreach ($tasks as $task) {
        if ($task->allowedToRun() && $task->shouldRun()) {
          $results[] = $task->execute($params);
        }
      }
    }

    return $results;
  }

  /**
   * Check if the current user has enough permissions to run the task
   */
  public function allowedToRun() {
    // get permissions
    $run_permissions = $this->getAttribute('run_permissions');
    if (empty($run_permissions)) {
      $run_permissions = ['administer CiviCRM'];
    } else {
      $run_permissions = explode(',', $run_permissions);
    }

    // check if the user has at least one of them
    $is_allowed = CRM_Core_Permission::check([$run_permissions]);
    if (!$is_allowed) {
      $this->log("User does not have enough permissions to run task [{$this->getID()}]");
    }
    return $is_allowed;
  }

  /**
   * Check if the task should run according to scheduling
   */
  public function shouldRun() {
    $last_execution = strtotime($this->getAttribute('last_execution'));
    // if never ran, we need any day to compare
    if (empty($last_execution)) {
      $last_execution = strtotime('1970-01-01 00:00:00');
    }
    $scheduled = $this->getAttribute('scheduled');

    // if it should always be executed
    //  => YES!
    if ($scheduled == 'always') {
      return TRUE;
    }

    if (!empty($this->config['scheduled_month'])) {
      $scheduled_month = str_pad($this->config['scheduled_month'], 2, '0', STR_PAD_LEFT);
    }
    else {
      // January
      $scheduled_month = '01';
    }
    if (!empty($this->config['scheduled_weekday'])) {
      $scheduled_weekday = $this->config['scheduled_weekday'];
    }
    else {
      $scheduled_weekday = '1';
    }
    if (!empty($this->config['scheduled_day'])) {
      $scheduled_day = str_pad($this->config['scheduled_day'], 2, '0', STR_PAD_LEFT);
    }
    else {
      $scheduled_day = '01';
    }
    if (!empty($this->config['scheduled_hour'])) {
      $scheduled_hour = str_pad($this->config['scheduled_hour'], 2, '0', STR_PAD_LEFT);
    }
    else {
      $scheduled_hour = '00';
    }
    if (!empty($this->config['scheduled_minute'])) {
      $scheduled_minute = str_pad($this->config['scheduled_minute'], 2, '0', STR_PAD_LEFT);
    }
    else {
      $scheduled_minute = '00';
    }

    $now = CRM_Utils_Date::currentDBDate();
    // last time the task was executed, with minute resolution
    $lastFormattedDate = date('YmdHi', $last_execution);
    // current date with minute resolution
    $currentFormattedDate = date('YmdHi', strtotime($now));
    // current execution slot date according to the scheduler settings.
    // it's set based on the current time and scheduler settings
    // examples (assuming now = June 11th, 2019 13:00:
    // | frequency | month | day | hour | minute | $currentScheduledDate |
    // | hourly    |       |     |      |     30 | 201906111330          |
    // | daily     |       |     |   14 |     30 | 201906111430          |
    // | weekly    |       | Wed |   14 |     30 | 201924314-30          |
    // | monthly   |       |  12 |   14 |     30 | 201906121430          |
    // | annually  |   Jul |  13 |   14 |     30 | 201907131430          |
    $currentScheduledDate = NULL;
    switch ($scheduled) {
      case 'hourly':
        $currentScheduledDate = date('YmdH', strtotime($now)) . $scheduled_minute;
        break;

      case 'daily':
        $currentScheduledDate = date('Ymd', strtotime($now)) . $scheduled_hour . $scheduled_minute;
        break;

      case 'weekly':
        $currentFormattedDate = date('oWNHi', strtotime($now));
        $lastFormattedDate = date('oWNHi', $last_execution);
        $currentScheduledDate = date('oW', strtotime($now)) . $scheduled_weekday . $scheduled_hour . $scheduled_minute;
        break;

      case 'monthly':
        $currentScheduledDate = date('Ym', strtotime($now)) . $scheduled_day . $scheduled_hour . $scheduled_minute;
        break;

      case 'yearly':
        $currentScheduledDate = date('Y', strtotime($now)) . $scheduled_month . $scheduled_day . $scheduled_hour . $scheduled_minute;
        break;

    }
    // checks:
    // - is the current date after or on the next execution date (i.e. is it due?)
    // AND
    // - was the last execution before the next execution date (i.e. was the task already executed?)
    return $currentFormattedDate >= $currentScheduledDate && $lastFormattedDate < $currentScheduledDate;
  }

  /**
   * get the option for scheduling (simple version)
   */
  public static function getSchedulingOptions() {
    $frequencies = [
      'always'  => E::ts('always'),
      'hourly'  => E::ts('every hour'),
      'daily'   => E::ts('every day (after midnight)'),
      'weekly'  => E::ts('every week'),
      'monthly' => E::ts('every month'),
      'yearly'  => E::ts('annually'),
    ];

    // get scheduler information
    $config = CRM_Sqltasks_Config::singleton();
    $dispatcher_frequency = $config->getCurrentDispatcherFrequency();
    switch ($dispatcher_frequency) {
      case 'Always':
        break;

      case 'Hourly':
        $frequencies['always'] = $frequencies['always'] . ' ' . E::ts("(currently triggered hourly)");
        break;

      case 'Daily':
        $frequencies['always'] = $frequencies['always'] . ' ' . E::ts("(currently triggered daily)");
        $frequencies['hourly'] = $frequencies['hourly'] . ' ' . E::ts("(currently triggered daily)");
        break;

      default:
        // add a warning to all entries
        foreach ($frequencies as $key => &$value) {
          $value = $value . ' ' . E::ts("(warning: dispatcher currently disabled)");
        }
        break;
    }

    return $frequencies;
  }

  /**
   * calculate the next execution date
   */
  public static function getNextExecutionTime() {
    // TODO:
    // 1) find out if cron-job is there/enabled
    // 2) find out how often it runs
    // 3) calculate next date based on last exec date

    return 'TODO';
  }

  /**
   * Get the list of all files that have been registered by the task
   *
   * @return array list of file metadata
   */
  public static function getAllFiles() {
    return self::$files;
  }

  /**
   * Get the last registered file
   *
   * @return null|array file metadata
   */
  public static function getLastFile() {
    return end(self::$files);
  }

  /**
   * Returns prepared task
   *
   * @return array
   */
  public function getPreparedTask() {
    $data = [
      'id'             => $this->getID(),
      'name'           => $this->getAttribute('name'),
      'description'    => $this->getAttribute('description'),
      'category'       => $this->getAttribute('category'),
      'schedule_label' => $this->prepareSchedule($this->getAttribute('scheduled')),
      'schedule'       => $this->getAttribute('scheduled'),
      'scheduled'      => $this->getAttribute('scheduled'),
      'run_permissions'=> $this->getAttribute('run_permissions'),
      'last_executed'  => $this->prepareDate($this->getAttribute('last_execution')),
      'last_runtime'   => $this->prepareRuntime($this->getAttribute('last_runtime')),
      'parallel_exec'  => $this->getAttribute('parallel_exec'),
      'input_required' => $this->getAttribute('input_required'),
      'next_execution' => 'TODO',
      'enabled'        => (empty($this->getAttribute('enabled'))) ? 0 : 1,
      'config'         => $this->getConfiguration(),
    ];

    if (strlen($data['description']) > 64) {
      $data['short_desc'] = substr($data['description'], 0, 64) . '...';
    } else {
      $data['short_desc'] = $data['description'];
    }

    return $data;
  }

  /**
   * Prepares a date
   *
   * @param $string
   *
   * @return false|string
   */
  protected function prepareDate($string) {
    if (empty($string)) {
      return E::ts('never');
    } else {
      return date('Y-m-d H:i:s', strtotime($string));
    }
  }

  /**
   * Prepares a scheduling option
   *
   * @param $string
   *
   * @return mixed|string
   */
  protected function prepareSchedule($string) {
    $options = CRM_Sqltasks_Task::getSchedulingOptions();
    if (isset($options[$string])) {
      return $options[$string];
    } else {
      return E::ts('ERROR');
    }
  }

  /**
   * Prepares an integer microtime value
   *
   * @param $value
   *
   * @return mixed|string
   */
  protected function prepareRuntime($value) {
    if (!$value) {
      return E::ts('n/a');
    } elseif ($value > (1000 * 60)) {
      // render values > 1 minute as min:second
      $minutes = $value / (1000 * 60);
      $seconds = ($value % (1000 * 60)) / 1000;
      return sprintf("%d:%02d min", $minutes, $seconds);
    } else {
      // render values < 1 minute as 0.000 seconds
      return sprintf("%d.%03ds", ($value/1000), ($value%1000));
    }
  }

}
