<?php
use CRM_Sqltasks_ExtensionUtil as E;

/**
 * Sort.Resort API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sortsqltasks_Resort_spec(&$params) {

    $params['data'] = array(
        'name'         => 'data',
        'api.required' => 1,
        'type'         => CRM_Utils_Type::T_STRING,
        'title'        => 'Task order/weight data ',
        'description'  => 'Resort tasks and save them to database.',
    );
}

/**
 * Sort.Resort API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_sortsqltasks_Resort($params) {

    try {

        $tasksOrder = $params['data'];

        foreach($tasksOrder as $key => $task) {

            $task = explode('_', $task);
            $weight = ($key*10) + 10;

            $query = "UPDATE civicrm_sqltasks SET weight = %1 WHERE id = %2";
            $sqlParams = array(
                1 => array($weight, 'String'),
                2 => array($task[0], 'Integer'));
            CRM_Core_DAO::executeQuery($query, $sqlParams);

        }

        return civicrm_api3_create_success(array(True));

    } catch (Exception $e) {
        return civicrm_api3_create_error($e->getMessage());
    }
}
