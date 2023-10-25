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

use Civi\Utils\Sqltasks\Settings;
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
    'archive_date'    => 'String',
    // REMOVED - DO NOT USE
    'main_sql'        => 'String',
    // REMOVED - DO NOT USE
    'post_sql'        => 'String',
    'input_required'  => 'Integer',
    'abort_on_error'  => 'Integer',
    'last_modified'   => 'Date',
  ];

  protected $attributes;
  protected $config;
  protected $detailedTaskLogs;
  protected $error_count;
  protected $log_messages;
  protected $log_to_file = FALSE;
  protected $status;
  protected $task_id;

  /**
   * Constructor
   *
   * @param $task_id
   * @param array $data
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

    return $is_allowed;
  }

  /**
   * Archive the task
   */
  public function archive() {
    $this->setAttribute('enabled', 0);
    $this->setAttribute('archive_date', date('Y-m-d H:i:s'));
    $this->store();
  }

  /**
   * Static wrapper for Sqltask.execute
   */
  public static function callSqltaskExecute($_ctx, $params) {
    civicrm_api3('Sqltask', 'execute', $params);

    return TRUE;
  }

  /**
   * Delete a task with the given ID
   *
   * @param $tid
   * @return null
   */
  public static function delete($tid) {
    $tid = (int) $tid;
    if (empty($tid)) return NULL;
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_sqltasks WHERE id = {$tid}");
  }

  /**
   * @param $writeTrough
   * @return void
   */
  public function disableTask($writeTrough = FALSE) {
    $this->attributes['enabled'] = 0;

    if (empty($this->task_id)) {
      return;
    }

    $data = CRM_Sqltasks_Task::getDataAboutIfAllowToToggleTask($this->task_id);
    if (!$data['disabling']['isAllow']) {
      return;
    }

    if ($writeTrough) {
      CRM_Core_DAO::executeQuery("UPDATE `civicrm_sqltasks` SET `enabled` = 0 WHERE id = %1 ",
        [1 => [$this->task_id, 'Integer']]
      );
    }
  }

  /**
   * @param $writeTrough
   * @return void
   */
  public function enableTask($writeTrough = FALSE) {
    $this->attributes['enabled'] = 1;

    if (empty($this->task_id)) {
      return;
    }

    if ($writeTrough) {
      CRM_Core_DAO::executeQuery("UPDATE `civicrm_sqltasks` SET `enabled` = 1 WHERE id = %1 ",
        [1 => [$this->task_id, 'Integer']]
      );
    }
  }

  /**
   * Executes the given task
   *
   * @param array $params
   * @return array
   */
  public function execute($params = []) {
    $input_value = $params['input_val'] ?? NULL;

    if (empty($params['execution_id'])) {
      $execution = CRM_Sqltasks_BAO_SqltasksExecution::create([
        'input'       => $input_value,
        'log_to_file' => !empty($params['log_to_file']),
        'sqltask_id'  => $this->task_id,
      ]);
    } else {
      $exec_props = CRM_Sqltasks_BAO_SqltasksExecution::getById($params['execution_id']);
      $execution = new CRM_Sqltasks_BAO_SqltasksExecution($exec_props);
    }

    $execution->start();
    $execution->logInfo('Start running task!');

    if ($this->isArchived()) {
      $execution->reportError('Task is archived. Execution skipped.');
      $execution->stop();

      return $execution->result();
    }

    if ($this->isRunning() && !$this->parallelExecAllowed()) {
      $execution->reportError('Task is still running. Execution skipped.');
      $execution->stop();

      return $execution->result();
    }

    if (!$this->parallelExecAllowed()) {
      $lock = new CRM_Core_Lock($this->getLockName());
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

    $this->setTaskRunning(TRUE);

    $actions = CRM_Sqltasks_Action::getAllActiveActions($this);

    $context = [
      'actions'   => $actions,
      'execution' => $execution,
      'random'    => CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC),
    ];

    if ($this->inputRequired() && !empty($input_value)) {
      $context['input_val'] = $input_value;
      $execution->logInfo("Set input val to '$input_value'.");
    }

    foreach ($actions as $action) {
      $action_name = $action->getName();

      if (
        $execution->hasErrors()
        && $this->getAttribute("abort_on_error")
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
        $execution->reportError("Configuration Error '$action_name': " . $e -> getMessage());
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
        $execution->reportError("Error in action '$action_name': " . $e -> getMessage());
      }
    }

    $execution->logInfo("Finished task execution.");
    $execution->stop();
    $this->setTaskRunning(FALSE, $execution->runtime);

    if (!$this->parallelExecAllowed()) $lock->release();

    return $execution->result();
  }

  /**
   * Add the task to a queue for background execution
   */
  public function executeAsync($params = []) {
    $task_id = $this->task_id;

    $execution = CRM_Sqltasks_BAO_SqltasksExecution::create([
      'input'       => $params['input_val'] ?? NULL,
      'log_to_file' => !empty($params['log_to_file']),
      'sqltask_id'  => $this->task_id,
      'start_date'  => NULL,
    ]);

    $queue = Civi::queue("sqltask-$task_id", [
      'error'  => 'delete',
      'reset'  => FALSE,
      'runner' => 'task',
      'type'   => 'SqlParallel',
    ]);

    $queue_task = new CRM_Queue_Task(
      ['CRM_Sqltasks_Task', 'callSqltaskExecute'],
      [
        [
          'async'        => FALSE,
          'execution_id' => $execution->id,
          'id'           => $task_id,
          'input_val'    => $params['input_val'],
          'log_to_file'  => $params['log_to_file'],
        ],
      ],
      "SQL Task $task_id"
    );

    $queue_task->runAs = [
      'contactId' => CRM_Core_Session::getLoggedInContactID(),
      'domainId'  => 1,
    ];

    $queue->createItem($queue_task);

    return [ 'execution_id' => $execution->id ];
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
    unset($config['archive_date']);
    $config['config'] = $this->config;
    return json_encode($config, JSON_PRETTY_PRINT);
  }

  /**
   * Returns task ids which uses this tasks in config(json) field
   *
   * @param $taskId
   * @return array
   */
  public static function findTaskIdsWhichUsesTask($taskId) {
    if (empty($taskId)) {
      return [];
    }

    $query = "
        SELECT id FROM civicrm_sqltasks
        WHERE FIND_IN_SET(
            " . $taskId . ",
            REPLACE(REPLACE(REPLACE(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(config, '$.actions[*].tasks')), '[', ''), ']', ''), '\"', ''), ' ', '')
        );
    ";

    $taskIds = [];
    $task = CRM_Core_DAO::executeQuery($query);

    while ($task->fetch()) {
      $taskIds[] = $task->id;
    }

    return $taskIds;
  }

  /**
   * Fix config at 'CallTask' action:
   * Clears task which not allow to run
   *
   * @return array
   */
  public static function fixConfigAtCallTaskAction($config) {
    if (empty($config) || !is_array($config) || empty($config['actions'])) {
      return $config;
    }

    foreach ($config['actions'] as $key => $action) {
      if ($action['type'] === CRM_Sqltasks_Action_CallTask::class && !empty($action['tasks']) && is_array($action['tasks'])) {

        $isExecuteDisabledTasks = false;
        if (!empty($action['is_execute_disabled_tasks']) && $action['is_execute_disabled_tasks'] == 1) {
          $isExecuteDisabledTasks = true;
        }

        $cleanedTasks = [];
        foreach ($action['tasks'] as $taskId) {
          $task = CRM_Core_DAO::executeQuery(
            "SELECT `id`, `enabled`, `archive_date` FROM civicrm_sqltasks WHERE `id` = %1 LIMIT 1;",
            [1 => [$taskId, "Integer"]]
          );

          while ($task->fetch()) {
            $isTaskArchived = !empty($task->archive_date);
            $isTaskDisabled = $task->enabled == 0;

            if ($isTaskArchived) {
              continue;
            }

            if (!$isExecuteDisabledTasks && $isTaskDisabled) {
              continue;
            }

            $cleanedTasks[] = $taskId;
          }

          $config['actions'][$key]['tasks'] = $cleanedTasks;
        }
      }
    }

    return $config;
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
   * Get a single attribute from the task
   *
   * @param $attribute_name
   *
   * @return mixed
   */
  public function getAttribute($attribute_name) {
    return CRM_Utils_Array::value($attribute_name, $this->attributes);
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
   * Get configuration
   */
  public function getConfiguration() {
    return $this->config;
  }

  /**
   * @return string
   */
  public function getConfigureTaksLink() {
    if (empty($this->getID())) {
      return '';
    }

    return CRM_Utils_System::url('civicrm/a/', NULL, TRUE, "/sqltasks/configure/{$this->getID()}");
  }

  /**
   * @param $taskId
   * @return array
   */
  public static function getDataAboutIfAllowToToggleTask($taskId) {
    $data = [
      'enabling' => [
        'isAllow' => true,
        'allRelatedTasks' => [],
        'skippedRelatedTasks' => [],
        'notSkippedRelatedTasks' => [],
      ],
      'disabling' => [
        'isAllow' => true,
        'allRelatedTasks' => [],
        'skippedRelatedTasks' => [],
        'notSkippedRelatedTasks' => [],
      ],
    ];

    if (empty($taskId)) {
      return $data;
    }

    $taskIds = CRM_Sqltasks_Task::findTaskIdsWhichUsesTask($taskId);
    $taskObjects = CRM_Sqltasks_Task::getTaskObjectsByIds($taskIds);

    foreach ($taskObjects as $task) {
      $configuration = $task->getConfiguration();
      $isNeedToSkipTask = true;

      if (empty($configuration['actions'])) {
        continue;
      }

      foreach ($configuration['actions'] as $action) {
        if ($action['type'] != 'CRM_Sqltasks_Action_CallTask') {
          continue;
        }
        $isExecuteDisabledTasks = (isset($action['is_execute_disabled_tasks']) && $action['is_execute_disabled_tasks'] == 1);
        if ($isExecuteDisabledTasks) {
          continue;
        }

        if (!empty($action['tasks']) && is_array($action['tasks'])) {
          foreach ($action['tasks'] as $actionTaskId) {
            if ($actionTaskId == $taskId) {
              $isNeedToSkipTask = false;
            }
          }
        }
      }

      if ($isNeedToSkipTask) {
        $data['enabling']['skippedRelatedTasks'][] = $task;
        $data['disabling']['skippedRelatedTasks'][] = $task;
      } else {
        $data['enabling']['notSkippedRelatedTasks'][] = $task;
        $data['disabling']['notSkippedRelatedTasks'][] = $task;
      }

      $data['enabling']['allRelatedTasks'] = $taskObjects;
      $data['disabling']['allRelatedTasks'] = $taskObjects;
    }

    if (!empty($data['disabling']['notSkippedRelatedTasks'])) {
      $data['disabling']['isAllow'] = false;
    }

    if (!empty($data['enabling']['notSkippedRelatedTasks'])) {
      $data['enabling']['isAllow'] = false;
    }

    return $data;
  }

  /**
   * Get a list of tasks ready for execution
   */
  public static function getExecutionTaskList() {
    return self::getTasks('SELECT * FROM civicrm_sqltasks WHERE enabled=1 ORDER BY weight ASC, id ASC');
  }

  /**
   * Get a list of tasks ready for execution which prepared for select
   *
   * @return array
   */
  public static function getExecutionTaskListOptions($params) {
    $queryParams = [];
    $query = 'SELECT `id`, `name`, `enabled`, `archive_date` FROM civicrm_sqltasks ';
    if ($params['isShowDisabledTasks'] == 0) {
      $query .= ' WHERE enabled = 1 ';
    } else {
      $query .= ' WHERE archive_date IS NULL ';
    }

    if (!empty($params['excludedTaskId'])) {
      $query .= ' AND id <> %1 ';
      $queryParams[1] = [$params['excludedTaskId'], 'Integer'];
    }

    $query .= ' ORDER BY weight ASC, id ASC';

    $options = [];
    $task = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($task->fetch()) {
      $icon = 'sql-task-custom-toggle-icon ' . (($task->enabled == 1) ? 'fa-toggle-on' : 'fa-toggle-on fa-flip-horizontal');

      $options[] = [
        'name' => "[{$task->id}] " . $task->name,
        'value' => $task->id,
        'icon' => $icon,
      ];
    }

    return $options;
  }

  /**
   * Get a single attribute from the task
   */
  public function getID() {
    return $this->task_id;
  }

  /**
   * Generates lock name to the task
   *
   * @return string
   */
  private function getLockName() {
    return 'civicrm_de_systopia_sqltasks_task_id_' . $this->getID();
  }

  /**
   * Calculate the next execution date
   */
  public static function getNextExecutionTime() {
    // TODO:
    // 1) find out if cron-job is there/enabled
    // 2) find out how often it runs
    // 3) calculate next date based on last exec date

    return 'TODO';
  }

  /**
   * Get list of ordered tasks ids
   *
   * @return array
   */
  public static function getOrderedTasks() {
    $result = CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_sqltasks ORDER BY weight ASC, id ASC');
    $tasksOrder = [];

    while ($result->fetch()) {
      $tasksOrder[] = $result->id;
    }

    return $tasksOrder;
  }

  /**
   * Get a list of tasks ready for execution
   */
  public static function getParallelExecutionTaskList() {
    return self::getTasks('SELECT * FROM civicrm_sqltasks WHERE enabled=1 AND parallel_exec IN (1, 2) ORDER BY weight ASC, id ASC');
  }

  /**
   * Returns prepared task
   *
   * @return array
   */
  public function getPreparedTask() {
    $data = [
      'id'                      => $this->getID(),
      'name'                    => $this->getAttribute('name'),
      'description'             => $this->getAttribute('description'),
      'short_desc'              => $this->prepareShortDescription($this->getAttribute('description')),
      'category'                => $this->getAttribute('category'),
      'schedule_label'          => $this->prepareSchedule($this->getAttribute('scheduled')),
      'schedule'                => $this->getAttribute('scheduled'),
      'scheduled'               => $this->getAttribute('scheduled'),
      'run_permissions'         => $this->getAttribute('run_permissions'),
      'last_executed'           => $this->prepareDate($this->getAttribute('last_execution')),
      'last_runtime'            => $this->prepareRuntime($this->getAttribute('last_runtime')),
      'last_modified'           => $this->getAttribute("last_modified"),
      'parallel_exec'           => $this->getAttribute('parallel_exec'),
      'input_required'          => $this->getAttribute('input_required'),
      'next_execution'          => 'TODO',
      'enabled'                 => (empty($this->getAttribute('enabled'))) ? 0 : 1,
      'config'                  => $this->getConfiguration(),
      'is_archived'             => (int) $this->isArchived(),
      'archive_date'            => (empty($this->getAttribute('archive_date'))) ? '' : $this->getAttribute('archive_date'),
      'abort_on_error'          => $this->getAttribute('abort_on_error'),
    ];

    return $data;
  }

  /**
   * Get the option for scheduling (simple version)
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
   * Load a list of tasks based on the data yielded by the given SQL query
   *
   * @param $tid
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
   * Get a list of all SQL Task categories
   *
   * @return array
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
   * @param $taskIds
   * @return array
   */
  public static function getTaskObjectsByIds($taskIds) {
    if (empty($taskIds)) {
      return [];
    }

    $taskObjects = [];

    foreach ($taskIds as $taskId) {
      $taskObjects[] = CRM_Sqltasks_Task::getTask($taskId);
    }

    return $taskObjects;
  }

  /**
   * Get tasks options prepared for html select
   *
   * @return array
   */
  public static function getTaskOptions() {
    $dao = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_sqltasks');
    $tasksOptions = [];

    while ($dao->fetch()) {
      $tasksOptions[$dao->id] = $dao->name;
    }

    return $tasksOptions;
  }

  /**
   * Load a list of tasks based on the data yielded by the given SQL query
   *
   * @param $sql_query
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

  /*
   * @return boolean
   */
  public function inputRequired() {
    return (bool) $this->getAttribute('input_required');
  }

  /**
   * Is task archived?
   */
  public function isArchived() {
    return !empty($this->getAttribute('archive_date'));
  }

  /*
   * @return boolean
   */
  private function isRunning() {
    return (bool) CRM_Core_DAO::singleValueQuery(
      "SELECT running_since FROM `civicrm_sqltasks` WHERE id = %1",
      [ 1 => [(int) $this->task_id, 'Integer'] ]
    );
  }

  /*
   * @return boolean
   */
  private function parallelExecAllowed() {
    return ((int) $this->getAttribute('parallel_exec')) === 2;
  }

  private function setTaskRunning($running, $task_runtime = 0) {
    if ($running) {
      CRM_Core_DAO::executeQuery(
        "UPDATE `civicrm_sqltasks` SET last_execution = NOW(), running_since = NOW() WHERE id = %1",
        [ 1 => [(int) $this->task_id, 'Integer'] ]
      );
    } else {
      CRM_Core_DAO::executeQuery(
        "UPDATE `civicrm_sqltasks` SET running_since = NULL, last_runtime = %1 WHERE id = %2;",
        [
          1 => [$task_runtime, 'Integer'],
          2 => [(int) $this->task_id, 'Integer'],
        ]
      );
    }
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
   * Prepares a short description
   *
   * @param $description
   * @return false|string
   */
  protected function prepareShortDescription($description) {
    if (strlen($description) > 64) {
      return substr($description, 0, 64) . '...';
    }

    return $description;
  }

  /**
   * Main dispatcher, triggered by a scheduled Job
   *
   * @param array $params
   *
   * @return array
   */
  public static function runDispatcher($params = []) {
    $results = [];

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
    } else {
      // PARALLEL DISPATCH: only run tasks flagged as parallel
      $tasks = CRM_Sqltasks_Task::getParallelExecutionTaskList();
    }

    $maxFailsNumber = Settings::getMaxFailsNumber();
    $errorCount = 0;
    $successCount = 0;
    $skippedCount = 0;
    $notes = [];
    if (Settings::isDispatcherDisabled()) {
      $notes[] = 'Dispatcher is disabled. Skipping all task executions.';
    }

    foreach ($tasks as $task) {
      if (Settings::isDispatcherDisabled()) {
        $skippedCount++;
        continue;
      }

      if (!$task->isArchived() && $task->allowedToRun() && $task->shouldRun()) {
        $taskExecutionResult = $task->execute();
        $results[] = $taskExecutionResult['logs'];

        if ($taskExecutionResult['error_count'] > 0) {
          $errorCount++;
        }
        else {
          $successCount++;
        }

        if ($maxFailsNumber !== 0 && $errorCount >= $maxFailsNumber) {
          Settings::disableDispatcher();
          $notes[] = 'Dispatcher disabled after ' . $errorCount . ' errors';
        }
      } else {
        $skippedCount++;
      }
    }

    return [
      'tasks' => $results,
      'summary' => [
        'tasks' => count($tasks),
        'errors' => $errorCount,
        'success' => $successCount,
        'skipped' => $skippedCount,
        'notes' => $notes,
      ],
    ];
  }

  /**
   * Set a single attribute
   *
   * @param $attribute_name
   * @param $value
   * @param bool $writeTrough
   * @throws Exception
   */
  public function setAttribute($attribute_name, $value, $writeTrough = FALSE) {
    if ($attribute_name === 'enabled') {
      if ($value == 1) {
        $this->enableTask($writeTrough);
      } else {
        $this->disableTask($writeTrough);
      }

      return;
    }

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
   * Set entire configuration
   *
   * @param $config
   * @param bool $writeTrough
   *
   * @return array
   * @throws Exception
   */
  public function setConfiguration($config, $writeTrough = FALSE) {
    $config['version'] = CRM_Sqltasks_Config_Format::getVersion($config);
    $config = CRM_Sqltasks_Task::fixConfigAtCallTaskAction($config);

    if ($writeTrough && $this->task_id) {
      $this->attributes["last_modified"] = date("Y-m-d H:i:s");

      CRM_Core_DAO::executeQuery(
        "UPDATE `civicrm_sqltasks`
         SET `config` = %1,
             `last_modified` = %2
         WHERE id = %3",
        [
          1 => [json_encode($config), 'String'],
          2 => [$this->attributes["last_modified"], "String"],
          3 => [$this->task_id, 'Integer'],
        ]
      );
    }
    return $this->config = $config;
  }

  /**
   * Set default values for some attributes
   */
  private function setDefaultAttributes() {
    $defaults = [
      'abort_on_error'          => 0,
      'input_required'          => 0,
      'parallel_exec'           => 0,
    ];

    foreach ($defaults as $attribute => $value) {
      if (empty($this->attributes[$attribute])) {
        $this->attributes[$attribute] = $value;
      }
    }
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
   * Store this task (create or update)
   */
  public function store() {
    $this->setDefaultAttributes();
    // sort out parameters
    $params = array();
    $fields = array();
    $index  = 1;
    $this->attributes["last_modified"] = date("Y-m-d H:i:s");
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
        if ($attribute_type === "Date") {
          $attribute_type = "String";
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
   * Unarchive the task
   */
  public function unarchive() {
    $this->setAttribute('archive_date', NULL);
    $this->store();
  }

  /**
   * Update order of tasks
   *
   * @param $newTasksOrder
   */
  public static function updateTasksOrder($newTasksOrder) {
    foreach ($newTasksOrder as $key => $taskId) {
      CRM_Core_DAO::executeQuery(
        'UPDATE civicrm_sqltasks SET weight = %1 WHERE id = %2',
        [
          1 => [($key * 10) + 10, 'String'],
          2 => [$taskId, 'Integer'],
        ]
      );
    }
  }

}
