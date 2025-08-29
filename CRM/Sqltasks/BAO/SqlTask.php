<?php

use Civi\Utils\Sqltasks\Settings;
// phpcs:disable
use CRM_Sqltasks_ExtensionUtil as E;
// phpcs:enable

class CRM_Sqltasks_BAO_SqlTask extends CRM_Sqltasks_DAO_SqlTask {

  const PARALLEL_EXEC_ALLOWED = 2;

  private static $schedulingOptions = [
    'always',
    'hourly',
    'daily',
    'weekly',
    'monthly',
    'yearly',
  ];

  /**
   * Constructor
   *
   * @param array $params
   */
  public function __construct($params = []) {
    parent::__construct();
    $this->updateAttributes($params, [ 'save' => FALSE ]);
  }

  /**
   * Check whether the current user has enough permissions to run the task
   *
   * @return bool
   */
  public function allowedToRun() {
    $run_permissions = empty($this->run_permissions)
      ? ['administer CiviCRM']
      : explode(',', $this->run_permissions);

    return CRM_Core_Permission::check([$run_permissions]);
  }

  /**
   * Archive the task
   */
  public function archive() {
    $this->updateAttributes([
      'archive_date' => date('Y-m-d H:i:s'),
      'enabled'      => FALSE,
    ]);
  }

  /**
   * Add a task to a queue for background execution
   *
   * @param array $params
   * @return array
   */
  public function enqueue($params = []) {
    $execution = CRM_Sqltasks_BAO_SqltasksExecution::create([
      'input'       => $params['input_val'] ?? NULL,
      'log_to_file' => !empty($params['log_to_file']),
      'sqltask_id'  => $this->id,
      'start_date'  => NULL,
    ]);

    $queue = Civi::queue("sqltask-{$this->id}", [
      'error'  => 'delete',
      'reset'  => FALSE,
      'runner' => 'task',
      'type'   => 'SqlParallel',
    ]);

    $queue_task = new CRM_Queue_Task(
      ['CRM_Sqltasks_BAO_SqlTask', 'executeTask'],
      [
        [
          'async'        => FALSE,
          'execution_id' => $execution->id,
          'id'           => $this->id,
          'input_val'    => $params['input_val'] ?? NULL,
          'log_to_file'  => $params['log_to_file'] ?? 0,
        ],
      ],
      "SQL Task {$this->id}"
    );

    $queue_task->runAs = [
      'contactId' => CRM_Core_Session::getLoggedInContactID(),
      'domainId'  => 1,
    ];

    $queue->createItem($queue_task);

    return [ 'execution_id' => $execution->id ];
  }

  /**
   * Executes the task
   *
   * @param array $params
   * @return array
   */
  public function execute($params = []) {
    if (empty($this->id)) return;

    $input_value = $params['input_val'] ?? NULL;

    if (is_array($input_value)) {
      $input_value = json_encode($input_value);
    }

    if (empty($params['execution_id'])) {
      $execution = CRM_Sqltasks_BAO_SqltasksExecution::create([
        'input'       => $input_value,
        'log_to_file' => !empty($params['log_to_file']),
        'sqltask_id'  => $this->id,
      ]);
    } else {
      $exec_props = CRM_Sqltasks_BAO_SqltasksExecution::getById($params['execution_id']);
      $execution = new CRM_Sqltasks_BAO_SqltasksExecution($exec_props);
    }

    $execution->start();
    $execution->logInfo('Start running task!');

    if (isset($this->archive_date)) {
      $execution->reportError('Task is archived. Execution skipped.');
      $execution->stop();

      return $execution->result();
    }

    $parallel_exec_allowed = $this->parallel_exec == self::PARALLEL_EXEC_ALLOWED;

    if (isset($this->running_since) && !$parallel_exec_allowed) {
      $execution->reportError('Task is still running (started ' . $this->running_since . '). Execution skipped.');
      $execution->stop();

      return $execution->result();
    }

    if (!$parallel_exec_allowed) {
      $lock = new CRM_Core_Lock($this->lockName());
      $lock->acquire();

      if (!$lock->isAcquired()) {
        $execution->reportError('Task is locked. Execution skipped.');
        $execution->stop();

        return $execution->result();
      }
    }

    $execution->logInfo("Starting task execution.");

    // Commit any pending transactions to ensure consistent behaviour
    CRM_Core_DAO::executeQuery("COMMIT");

    $this->updateAttributes([
      'last_execution' => date('Y-m-d H:i:s'),
      'running_since'  => date('Y-m-d H:i:s'),
    ], [ 'update_mod_timestamp' => FALSE ]);

    $actions = CRM_Sqltasks_Action::getAllActiveActions($this);

    $context = [
      'actions'   => $actions,
      'execution' => $execution,
      'random'    => CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC),
    ];

    if ($this->input_required && !empty($input_value)) {
      $context['input_val'] = $input_value;
      $execution->logInfo("Set input val to '$input_value'.");
    }

    foreach ($actions as $action) {
      $action_name = $action->getName();

      if (
        $execution->hasErrors()
        && $this->abort_on_error
        && get_class($action) !== "CRM_Sqltasks_Action_ErrorHandler"
      ) {
        $execution->logInfo("Skipped '$action_name' due to previous error");
        continue;
      }

      $timestamp = microtime(TRUE);
      $action->setContext($context);

      try {
        $action->checkConfiguration();
      } catch (Exception $e) {
        $execution->reportError("Configuration Error '$action_name': " . $e->getMessage());
        continue;
      }

      try {
        $action->execute();

        if (get_class($action) == "CRM_Sqltasks_Action_ReturnValue") {
          $execution->setReturnValue($action->return_key, $action->return_value);
        }

        $runtime = microtime(TRUE) - $timestamp;
        $log_message = sprintf("Action '%s' executed in %.3fs.", $action_name, $runtime);
        $execution->logInfo($log_message);
      } catch (Exception $e) {
        $execution->reportError("Error in action '$action_name': " . $e->getMessage());
      }
    }

    $execution->logInfo("Finished task execution.");
    $execution->stop();

    $this->updateAttributes([
      'last_runtime'  => $execution->runtime,
      'running_since' => NULL,
    ], [ 'update_mod_timestamp' => FALSE ]);

    if (!$parallel_exec_allowed) {
      $lock->release();
    }

    return $execution->result();
  }

  /**
   * Static wrapper for CRM_Sqltasks_BAO_SqlTask::execute
   *
   * @param CRM_Queue_TaskContext $_ctx
   * @param array $params
   * @return bool
   */
  public static function executeTask($_ctx, $params) {
    $task = CRM_Sqltasks_BAO_SqlTask::findById($params['id']);
    $result = $task->execute($params);

    return $result['status'] === 'success';
  }

  /**
   * Convert task data to an associative array
   *
   * @param array $attributes
   * @return array
   */
  public function exportData($attributes = []) {
    $archive_date = is_null($this->archive_date)
      ? NULL
      : date('Y-m-d H:i:s', strtotime($this->archive_date));

    $last_execution = is_null($this->last_execution)
      ? NULL
      : date('Y-m-d H:i:s', strtotime($this->last_execution));

    $last_modified = is_null($this->last_modified)
      ? NULL
      : date('Y-m-d H:i:s', strtotime($this->last_modified));

    $task_data = [
      'abort_on_error'  => (bool) $this->abort_on_error,
      'archive_date'    => $archive_date,
      'category'        => $this->category,
      'config'          => json_decode($this->config, TRUE),
      'description'     => $this->description,
      'enabled'         => !empty($this->enabled),
      'id'              => (int) $this->id,
      'input_required'  => (bool) $this->input_required,
      'last_execution'  => $last_execution,
      'last_modified'   => $last_modified,
      'last_runtime'    => (int) $this->last_runtime,
      'name'            => $this->name,
      'parallel_exec'   => $this->parallel_exec,
      'run_permissions' => $this->run_permissions,
      'scheduled'       => $this->scheduled,
      'weight'          => (int) $this->weight,
    ];

    if (empty($attributes)) return $task_data;

    foreach ($task_data as $name => $_) {
      if (!in_array($name, $attributes, TRUE)) {
        unset($task_data[$name]);
      }
    }

    return $task_data;
  }

  /**
   * Generator that iterates over all tasks in the database
   *
   * @param array $params
   * @return CRM_Sqltasks_BAO_SqlTask
   */
  public static function generator($params = []) {
    $bao = new self();

    foreach ($params as $key => $value) {
      $bao->$key = $value;
    }

    $bao->find();

    while ($bao->fetch()) {
      $task = clone $bao;
      yield $task;
    }
  }

  /**
   * Get a list of task IDs ordered by task weight
   *
   * @return array
   */
  public static function getTaskOrder() {
    $result = CRM_Core_DAO::executeQuery("
      SELECT id
      FROM civicrm_sqltasks
      ORDER BY weight ASC, id ASC
    ");

    $task_order = [];

    while ($result->fetch()) {
      $task_order[] = $result->id;
    }

    return $task_order;
  }

  /**
   * Find other tasks that depend on this task (that have references to this one
   * in their CRM_Sqltasks_Action_CallTask actions)
   *
   * @param int $task_id
   * @param bool $must_be_enabled
   *   If set to TRUE, search for tasks that depend on this task being enabled
   * @return array
   */
  public static function getDependentTasks($task_id, $must_be_enabled = FALSE) {
    $task_ids = [];

    foreach (self::generator() as $task) {
      $config = json_decode($task->config, TRUE);

      if (is_null($config)) continue;

      $call_task_actions = array_filter($config['actions'], fn ($action) =>
        $action['type'] === 'CRM_Sqltasks_Action_CallTask'
        && in_array($task_id, $action['tasks'])
      );

      if (empty($call_task_actions)) continue;

      if (!$must_be_enabled) {
        $task_ids[] = $task->id;
        continue;
      }

      foreach ($call_task_actions as $action) {
        if (!$action['is_execute_disabled_tasks']) {
          $task_ids[] = $task->id;
        }
      }
    }

    return $task_ids;
  }

  /**
   * Get available options for scheduling
   *
   * @return array
   */
  public static function getSchedulingOptions() {
    return self::$schedulingOptions;
  }

  /**
   * Main dispatcher, triggered by a scheduled Job
   *
   * @param array $params
   *
   * @return array
   */
  public static function runDispatcher($params = []) {
    $tasks = [];
    $results = [];
    $notes = [];

    $success_count = 0;
    $error_count = 0;
    $skipped_count = 0;
    $max_fails_number = Settings::getMaxFailsNumber();

    // Reset timed out tasks (after 23 hours)
    CRM_Core_DAO::executeQuery("
      UPDATE `civicrm_sqltasks`
      SET running_since = NULL
      WHERE running_since < (NOW() - INTERVAL 23 HOUR);
    ");

    // Find out whether there are still running tasks
    $still_running = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*)
      FROM `civicrm_sqltasks`
      WHERE running_since IS NOT NULL;
    ");

    foreach (self::generator([ 'enabled' => 1 ]) as $task) {
      if ($still_running && !in_array((int) $task->parallel_exec, [1, 2], TRUE)) continue;
      $tasks[] = $task;
    }

    usort($tasks, function ($task_a, $task_b) {
      $weight_diff = (int) $task_a->weight - (int) $task_b->weight;
      $id_diff = (int) $task_a->id - (int) $task_b->id;
      return $weight_diff !== 0 ? $weight_diff : $id_diff;
    });

    if (Settings::isDispatcherDisabled()) {
      $notes[] = 'Dispatcher is disabled. Skipping all task executions.';
    }

    foreach ($tasks as $task) {
      if (
        Settings::isDispatcherDisabled()
        || !is_null($task->archive_date)
        || !$task->allowedToRun()
        || !$task->shouldRun()
      ) {
        $skipped_count++;
        continue;
      }

      $exec_result = $task->execute();
      $results[] = $exec_result['logs'];

      if ($exec_result['error_count'] > 0) {
        $error_count++;
      } else {
        $success_count++;
      }

      if ($max_fails_number !== 0 && $error_count >= $max_fails_number) {
        Settings::disableDispatcher();
        $notes[] = "Dispatcher disabled after $error_count errors";
      }
    }

    return [
      'tasks'   => $results,
      'summary' => [
        'errors'  => $error_count,
        'notes'   => $notes,
        'skipped' => $skipped_count,
        'success' => $success_count,
        'tasks'   => count($tasks),
      ],
    ];
  }


  /**
   * Determine whether the task should be executed
   *
   * @return boolean
   */
  public function shouldRun() {
    if ($this->scheduled === 'always') return TRUE;

    $config = json_decode($this->config, TRUE);
    $scheduled_month = (int) ($config['scheduled_month'] ?? 1);
    $scheduled_day = (int) ($config['scheduled_day'] ?? 1);
    $scheduled_weekday = (int) ($config['scheduled_weekday'] ?? 1);
    $scheduled_hour = (int) ($config['scheduled_hour'] ?? 0);
    $scheduled_minute = (int) ($config['scheduled_minute'] ?? 0);

    $last_execution = new DateTime($this->last_execution ?? '1970-01-01 00:00:00');
    $now = new DateTime(CRM_Utils_Date::currentDBDate());
    $schedule_date = clone $now;

    list($year, $month, $week, $weekday, $day, $hour, $minute) = array_map(
      fn ($n) => (int) $n,
      explode('-', $now->format('Y-m-W-w-d-H-i'))
    );

    switch ($this->scheduled) {
      case 'hourly':
        $schedule_date->setTime($hour, $scheduled_minute);
        break;

      case 'daily':
        $schedule_date->setTime($scheduled_hour, $scheduled_minute);
        break;

      case 'weekly':
        $schedule_date->setISODate($year, $week, $scheduled_weekday);
        $schedule_date->setTime($scheduled_hour, $scheduled_minute);
        break;

      case 'monthly':
        $schedule_date->setDate($year, $month, $scheduled_day);
        $schedule_date->setTime($scheduled_hour, $scheduled_minute);
        break;

      case 'yearly':
        $schedule_date->setDate($year, $scheduled_month, $scheduled_day);
        $schedule_date->setTime($scheduled_hour, $scheduled_minute);
        break;
    }

    $last_exec_ts = $last_execution->getTimestamp();
    $now_ts = $now->getTimestamp();
    $scheduled_ts = $schedule_date->getTimestamp();

    return $scheduled_ts < $now_ts && $last_exec_ts < $scheduled_ts;
  }

  /**
   * Unarchive the task
   *
   * @return void
   */
  public function unarchive() {
    $this->updateAttributes([ 'archive_date' => NULL ]);
  }

  /**
   * Update task data
   *
   * @param array $params
   * @param array $options
   * @return void
   */
  public function updateAttributes($params, $options = []) {
    $save = $options['save'] ?? TRUE;
    $update_mod_timestamp = $options['update_mod_timestamp'] ?? TRUE;
    $null_keys = [];

    foreach ($params as $key => $value) {
      if (in_array($value, [NULL, ''], TRUE) && !self::fields()[$key]['required']) {
        $this->$key = 'null';
        $null_keys[] = $key;
        continue;
      }

      switch ($key) {
        case 'abort_on_error':
        case 'enabled':
        case 'input_required': {
          $value = $value === '' ? FALSE : $value;

          if (!in_array($value, [TRUE, FALSE, 1, 0, '1', '0'], TRUE)) {
            throw new Exception("Attribute '$key' must be a boolean");
          }

          $value = (bool) $value;

          if (
            $key === 'enabled'
            && !$value
            && isset($this->id)
            && !empty(self::getDependentTasks($this->id, TRUE))
          ) {
            throw new Exception("Task can not be disabled, other tasks depend on it");
          }

          $this->$key = $value;
          break;
        }

        case 'parallel_exec': {
          if (!in_array($value, [0, 1, 2, '0', '1', '2'], TRUE)) {
            throw new Exception("Attribute '$key' must be one of 0, 1 or 2");
          }

          $this->$key = (int) $value;
          break;
        }

        case 'last_runtime':
        case 'weight': {
          if (!CRM_Utils_Rule::positiveInteger($value)) {
            throw new Exception("Attribute '$key' must be a positive integer");
          }

          $this->$key = (int) $value;
          break;
        }

        case 'category':
        case 'description':
        case 'name':
        case 'run_permissions': {
          $this->$key = (string) $value;
          break;
        }

        case 'archive_date':
        case 'last_execution':
        case 'running_since': {
          if (strtotime($value) === FALSE) {
            throw new Exception("Attribute '$key' must be a date");
          }

          $this->$key = date('Y-m-d H:i:s', strtotime($value));
          break;
        }

        case 'config': {
          $value = is_string($value) ? json_decode($value, TRUE) : $value;
          $config = self::validateConfiguration($value);
          $this->$key = json_encode($config);
          break;
        }

        case 'scheduled': {
          if (!in_array($value, self::$schedulingOptions)) {
            throw new Exception("Attribute '$key' must be a valid interval");
          }

          $this->$key = (string) $value;
          break;
        }
      }
    }

    if ($save) {
      if ($update_mod_timestamp) {
        $this->last_modified = date('Y-m-d H:i:s');
      }

      $this->save();
    }

    foreach ($null_keys as $key) {
      $this->$key = NULL;
    }
  }

  /**
   * Update order of tasks
   *
   * @param array $new_task_order
   * @return void
   */
  public static function updateTaskOrder($new_task_order) {
    foreach ($new_task_order as $key => $task_id) {
      CRM_Core_DAO::executeQuery(
        'UPDATE civicrm_sqltasks SET weight = %1 WHERE id = %2',
        [
          1 => [($key + 1) * 10, 'Integer'],
          2 => [$task_id       , 'Integer'],
        ]
      );
    }
  }

/* --- Private methods -------------------------------------------------------------------------- */

  /**
   * Get the name for a database lock depending on the task ID
   *
   * @return string
   */
  private function lockName() {
    $task_id = $this->id ?? '';
    return "civicrm_de_systopia_sqltasks_task_id_$task_id";
  }

  /**
   * Parse and complete JSON configuration for a task
   *
   * @param array $config
   * @return array
   */
  private static function validateConfiguration($config) {
    if (!is_array($config)) {
      throw new Exception("Task configuration must be an associative array");
    }

    $config['version'] = $config['version'] ?? 1;
    $config['actions'] = $config['actions'] ?? [];

    foreach ($config['actions'] as $i => $action) {
      if (
        $action['type'] !== CRM_Sqltasks_Action_CallTask::class
        || empty($action['tasks'])
        || !is_array($action['tasks'])
      ) continue;

      $execute_disabled_tasks = !empty($action['is_execute_disabled_tasks']);

      $config['actions'][$i]['tasks'] = array_filter($action['tasks'],
        function ($task_id) use ($execute_disabled_tasks) {
          try {
            $task = self::findById($task_id);
          } catch (Exception $_) {
            return FALSE;
          }

          if (!is_null($task->archive_date)) return FALSE;
          if (!$execute_disabled_tasks && empty($task->enabled)) return FALSE;

          return TRUE;
        });
    }

    return $config;
  }

}
