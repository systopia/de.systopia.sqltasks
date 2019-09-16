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

/**
 * SQL Task Execution
 */
function civicrm_api3_sqltask_execute($params) {

  $exec_params = [
    'log_to_file' => $params['log_to_file'],
    'input_val' => $params['input_val'],
  ];
  // If task_id given run only this one task
  if (!empty($params['id'])) {
    $task = CRM_Sqltasks_Task::getTask($params['id']);
    if ($task->allowedToRun()) {
      $timestamp = microtime(TRUE);
      $result = $task->execute($exec_params);
      return civicrm_api3_create_success([
          "log"     => $result,
          "files"   => CRM_Sqltasks_Task::getAllFiles(),
          'runtime' => microtime(TRUE) - $timestamp,
      ]);
    } else {
      return civicrm_api3_create_error("Insufficient permissions to run task [{$params['task_id']}].");
    }
  }

  // DEFAULT MODE:
  //   run all enabled tasks according to schedule
  $results = CRM_Sqltasks_Task::runDispatcher($exec_params);
  if (!empty($params['log_to_file'])) {
    // don't return logs if we're logging to file, return count instead
    $results = count($results);
  }
  return civicrm_api3_create_success($results);
}

/**
 * SQL Task Execution
 */
function _civicrm_api3_sqltask_execute_spec(&$params) {
  $params['id'] = array(
    'name'         => 'id',
    'api.required' => 0,
    'api.aliases'  => ['task_id'],
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Task ID',
    'description'  => 'If given, only this task will run. Regardless of scheduling and time',
  );
  $params['log_to_file'] = array(
    'name'         => 'log_to_file',
    'api.required' => 0,
    'api.default'  => 0,
    'type'         => CRM_Utils_Type::T_BOOLEAN,
    'title'        => 'Log to a file?',
    'description'  => 'Log task output to a file instead of returning it in the API results?',
  );
}

/**
 * Sqltask.sort API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sqltask_sort_spec(&$params) {

  $params['data'] = array(
    'name'         => 'data',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'New taskorder/weight data ',
    'description'  => 'New taskorder for resorting and saving to database.',
  );

  $params['task_screen_order'] = array(
    'name'         => 'task_screen_order',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Old taskorder/weight data ',
    'description'  => 'Screen taskorder for comparing with new taskorder.',
  );
}

/**
 * Sqltask.sort API
 *
 * Updates the order of the SQL Tasks
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_sqltask_sort($params) {

  try {
    $tasksorderNew = $params['data'];
    $taskScreenOrder = $params['task_screen_order'];

    // create new taskorder array for comparison with old taskorder array
    foreach ($taskScreenOrder as $key => $task) {
      $task = explode('_', $task);
      $taskScreenOrder[$key] = $task[0];
    }

    // fetch the task sorting from database
    $query = "SELECT id FROM civicrm_sqltasks ORDER BY weight ASC, id ASC";
    $result = CRM_Core_DAO::executeQuery($query);
    $tasksorderDatabase = [];

    while ($result->fetch()) {
      $tasksorderDatabase[] = $result->id;
    }

    // create new taskorder array for comparison with old taskorder array
    foreach ($tasksorderNew as $key => $task) {
      $task = explode('_', $task);
      $tasksorderNew[$key] = $task[0];
    }

    // check the difference between taskorder array from database and the taskorder array from the screen
    if ($taskScreenOrder != $tasksorderDatabase) {
      return civicrm_api3_create_error('Task order was modified');
    }

    foreach ($tasksorderNew as $key => $task) {
      $weight = ($key * 10) + 10;
      $query = "UPDATE civicrm_sqltasks SET weight = %1 WHERE id = %2";
      $sqlParams = [
        1 => [$weight, 'String'],
        2 => [$task, 'Integer'],
      ];

      CRM_Core_DAO::executeQuery($query, $sqlParams);
    }

    return civicrm_api3_create_success(array(TRUE));
  }
  catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}

/**
 * Create or update a task
 *
 * @param $params
 *
 * @return array
 * @throws \Exception
 */
function civicrm_api3_sqltask_create(&$params) {
  if (!is_array($params['config'])) {
    return civicrm_api3_create_error('Parameter "config" must be an array');
  }

  $taskParamNames = [
    'name', 'description', 'category', 'scheduled', 'parallel_exec',
    'input_required'
  ];
  $taskParams = [];
  foreach ($taskParamNames as $name) {
    if (array_key_exists($name, $params)) {
      $taskParams[$name] = $params[$name];
    }
  }

  if (empty($params['id'])) {
    $newParams = $taskParams;
    if (array_key_exists('config', $params)) {
      $newParams += $params['config'];
    }
    $task = new CRM_Sqltasks_Task($params['id'], $newParams);
    $task->store();
  } else {
    $task = CRM_Sqltasks_Task::getTask($params['id']);
    foreach ($taskParams as $name => $value) {
      $task->setAttribute($name, $value, TRUE);
    }
    if (array_key_exists('config', $params)) {
      $task->setConfiguration($params['config'], TRUE);
    }
  }
  $result = $task->getAttributes();
  $result['config'] = $task->getConfiguration();
  $result['id'] = $task->getID();
  return civicrm_api3_create_success($result);
}

function _civicrm_api3_sqltask_create_spec(&$params) {
  $params['id'] = [
    'name'         => 'id',
    'api.required' => 0,
    'api.aliases'  => ['task_id'],
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Task ID',
    'description'  => 'Unique task ID',
  ];

  $params['name'] = [
    'name'         => 'name',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Name',
  ];

  $params['description'] = [
    'name'         => 'description',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Description',
  ];

  $params['category'] = [
    'name'         => 'category',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Category',
  ];

  $params['weight'] = [
    'name'         => 'weight',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Weight',
    'description'  => 'Determines the order in which tasks are executed (lower is executed earlier)'
  ];

  $params['scheduled'] = [
    'name'         => 'scheduled',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Schedule',
    'description'  => 'Frequency at which the task should be executed by cron',
    'options'      => [
      'always'  => 'always',
      'hourly'  => 'hourly',
      'daily'   => 'daily',
      'weekly'  => 'weekly',
      'monthly' => 'monthly',
      'yearly'  => 'yearly',
    ],
  ];

  $params['parallel_exec'] = [
    'name'         => 'parallel_exec',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_BOOLEAN,
    'title'        => 'Allow parallel execution?',
    'description'  => 'Whether to allow multiple instances of this task to run at the same time',
  ];

  $params['input_required'] = [
    'name'         => 'input_required',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_BOOLEAN,
    'title'        => 'Require user input?',
    'description'  => 'Whether this task requires user input prior to execution',
  ];

  $params['enabled'] = [
    'name'         => 'enabled',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_BOOLEAN,
    'title'        => 'Enable task?',
    'description'  => 'Whether to enable task execution by cron according to schedule',
  ];

  $params['config'] = [
    'name'         => 'config',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_TEXT,
    'title'        => 'Configuration',
    'description'  => 'Task configuration, including actions, as an array',
  ];
}

/**
 * Get task
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltask_get(&$params) {
  $task = CRM_Sqltasks_Task::getTask($params['id']);
  $result = $task->getAttributes();
  $result['config'] = $task->getConfiguration();
  $result['id'] = $task->getID();
  return civicrm_api3_create_success($result);
}

function _civicrm_api3_sqltask_get_spec(&$params) {
  $params['id'] = [
    'name' => 'id',
    'api.required' => 1,
    'api.aliases' => ['task_id'],
    'type' => CRM_Utils_Type::T_INT,
    'title' => 'Task ID',
    'description' => 'Unique task ID',
  ];
}

/**
 * Get all supported task actions
 *
 * @param $params
 *
 * @return array
 * @throws \ReflectionException
 */
function civicrm_api3_sqltask_gettaskactions(&$params) {
  $actions = CRM_Sqltasks_Action::getAllActions();

  return civicrm_api3_create_success($actions);
}
