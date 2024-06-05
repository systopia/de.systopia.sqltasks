<?php

/**
 * Create or update a task
 *
 * @param $params
 *
 * @return array
 * @throws \Exception
 */
function civicrm_api3_sqltask_create($params) {
  $task = new CRM_Sqltasks_BAO_SqlTask();

  // Load task data if it is an update
  if (!empty($params['id'])) {
    $task->id = $params['id'];
    $found = (bool) $task->find(TRUE);

    if (!$found) {
      return civicrm_api3_create_error("Task(id={$task->id}) does not exist.");
    }

    unset($params['id']);
  }

  // Reject update if task is archived
  if (!is_null($task->archive_date)) {
    return civicrm_api3_create_error(
      "Task(id={$task->id}) is archived. " .
      "Cannot update any fields. " .
      "To update fields please unarchive the task."
    );
  }

  // Prevent concurrent changes
  if (
    isset($params["last_modified"])
    && isset($task->last_modified)
    && strtotime($params["last_modified"]) !== strtotime($task->last_modified)
  ) {
    $last_modified_fmt = date('H:i:s, j M Y', strtotime($task->last_modified));

    return civicrm_api3_create_error(
      "This task has been modified by another user at $last_modified_fmt",
      [ "error_type" => "CONCURRENT_CHANGES" ]
    );

    unset($params['last_modified']);
  }

  // Update the task
  $task->updateAttributes($params);

  return civicrm_api3_create_success($task->exportData());
}

/**
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
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

  $params['run_permissions'] = [
    'name'         => 'run_permissions',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Run permissions',
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
    'type'         => CRM_Utils_Type::T_INT,
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

  $params['abort_on_error'] = [
    'name'         => 'abort_on_error',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_BOOLEAN,
    'title'        => 'Abort task execution on error?',
    'description'  => 'Whether this task should stop execution if an action produces an error',
  ];

  $params['last_modified'] = [
    'name'         => 'last_modified',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Last Modification Date',
    'description'  => 'Date/Time of the last configuration change',
  ];
}
