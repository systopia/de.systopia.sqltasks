<?php

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

    // check the difference between task order array from database and the task order array from the screen
    if ($taskScreenOrder != $tasksorderDatabase) {
      return civicrm_api3_create_error('Task order can\'t be modified. Task order on database must be equal with entered task order.');
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

    return civicrm_api3_create_success(['Task order have successfully modified.']);
  }
  catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
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

  $params['data'] = [
    'name'         => 'data',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'New taskorder/weight data ',
    'description'  => 'New taskorder for resorting and saving to database.',
  ];

  $params['task_screen_order'] = [
    'name'         => 'task_screen_order',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Old taskorder/weight data ',
    'description'  => 'Screen taskorder for comparing with new taskorder.',
  ];
}
