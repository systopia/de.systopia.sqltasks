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

require_once 'sqltasks.civix.php';
require_once 'CRM/Sqltasks/Config.php';
use CRM_Sqltasks_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function sqltasks_civicrm_config(&$config) {
  _sqltasks_civix_civicrm_config($config);

  Civi::dispatcher()->addListener(
    'hook_civicrm_pre',
    'CRM_Sqltasks_Utils::setCivirulesCustomFields',
    1
  );
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function sqltasks_civicrm_xmlMenu(&$files) {
  _sqltasks_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function sqltasks_civicrm_install() {
  _sqltasks_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function sqltasks_civicrm_postInstall() {
  _sqltasks_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function sqltasks_civicrm_uninstall() {
  _sqltasks_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function sqltasks_civicrm_enable() {
  _sqltasks_civix_civicrm_enable();
  CRM_Sqltasks_Config::installScheduledJob();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function sqltasks_civicrm_disable() {
  _sqltasks_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function sqltasks_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sqltasks_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function sqltasks_civicrm_managed(&$entities) {
  _sqltasks_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function sqltasks_civicrm_caseTypes(&$caseTypes) {
  _sqltasks_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function sqltasks_civicrm_angularModules(&$angularModules) {
  _sqltasks_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function sqltasks_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _sqltasks_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function sqltasks_civicrm_entityTypes(&$entityTypes) {
  _sqltasks_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function sqltasks_civicrm_navigationMenu(&$menu) {
  _sqltasks_civix_insert_navigation_menu($menu, 'Administer/System Settings', array(
    'label'      => E::ts('Manage SQL Tasks'),
    'name'       => 'sqltasks_manage',
    'url'        => 'civicrm/a/#/sqltasks/manage',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 0,
  ));
  _sqltasks_civix_insert_navigation_menu($menu, 'Contacts', array(
      'label'      => E::ts('My Tasks'),
      'name'       => 'sqltasks_mytasks',
      'url'        => 'civicrm/sqltasks/mytasks',
      'permission' => 'access CiviCRM',
      'operator'   => 'OR',
      'separator'  => 0,
  ));

  _sqltasks_civix_insert_navigation_menu($menu, 'Administer/System Settings/sqltasks_manage', array(
    'label'      => E::ts('Global Token Manager'),
    'name'       => 'global_token_manager',
    'url'        => 'civicrm/sqltasks/global-token-manager',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 0,
  ));

  _sqltasks_civix_insert_navigation_menu($menu, 'Administer/System Settings/sqltasks_manage', array(
    'label'      => E::ts('Templates'),
    'name'       => 'templates',
    'url'        => 'civicrm/sqltasks/templates',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 0,
  ));

  _sqltasks_civix_insert_navigation_menu($menu, 'Administer/System Settings/sqltasks_manage', [
    'label'      => E::ts('Execution Logs'),
    'name'       => 'sqltasks_execution_list',
    'url'        => 'civicrm/sqltasks-execution/list',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 0,
  ]);

  _sqltasks_civix_insert_navigation_menu($menu, 'Administer/System Settings/sqltasks_manage', [
    'label'      => E::ts('SQL Task Settings'),
    'name'       => 'sqltasks_settings',
    'url'        => 'civicrm/sqltask/settings',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 0,
  ]);

  _sqltasks_civix_navigationMenu($menu);
}

/**
 * alterAPIPermissions() hook allows you to change the permissions checked when doing API 3 calls.
 */
function sqltasks_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  // remark: permission check is on a task level
  $permissions['sqltask']['execute'] = array('access CiviCRM');
}

/**
 * Implements hook_civicrm_tokens().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_tokens/
 */
function sqltasks_civicrm_tokens(&$tokens) {
  $tokens['sqltasks'] = array(
    'sqltasks.downloadURL'   => E::ts("SQL Tasks: generated file download link"),
    'sqltasks.downloadTitle' => E::ts("SQL Tasks: generated file name"),
  );
}

/**
 * Implements hook_civicrm_tokenValues().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_tokenValues/
 */
function sqltasks_civicrm_tokenValues(&$values, $cids, $job = NULL, $tokens = array(), $context = NULL) {
  $files     = CRM_Sqltasks_BAO_SqltasksExecution::getAllFiles();
  $last_file = CRM_Sqltasks_BAO_SqltasksExecution::getLastFile();
  foreach ($cids as $cid) {
    $values[$cid]['sqltasks.downloadURL']   = $last_file['download_link'];
    $values[$cid]['sqltasks.downloadTitle'] = $last_file['title'];
    foreach ($files as $index => $file) {
      $values[$cid]["sqltasks.downloadURL_{$index}"]   = $file['download_link'];
      $values[$cid]["sqltasks.downloadTitle_{$index}"] = $file['title'];
    }
  }
}

/**
 * Implements hook_alterLogTables().
 *
 * @param array $logTableSpec
 */
function sqltasks_civicrm_alterLogTables(&$logTableSpec) {
  if (empty($logTableSpec) && is_array($logTableSpec)) {
    return;
  }

  // To apply those settings need turn off and then turn on logging at the setting page(civicrm/admin/setting/misc)
  // It recreates triggers at database and creates log tables if needed.
  // If exclude table form logs by 'alterLogTables' hook, it doesn't delete logs tables.
  // This logic works on CiviCRM 5.51.3.
  $excludedLogItems = [
    'tables' => [
      'civicrm_sqltasks_execution',
    ],
    'columns' => [
      'civicrm_sqltasks' => ['last_execution', 'running_since', 'last_runtime'],
    ]
  ];

  foreach ($excludedLogItems['tables'] as $excludedLogTable) {
    if (isset($logTableSpec[$excludedLogTable])) {
      unset($logTableSpec[$excludedLogTable]);
    }
  }

  foreach ($excludedLogItems['columns'] as $tableName => $columnNames) {
    if (isset($logTableSpec[$tableName])) {
      if (isset($logTableSpec[$tableName]['exceptions']) && is_array($logTableSpec[$tableName]['exceptions'])) {
        foreach ($columnNames as $columnName) {
          $logTableSpec[$tableName][] = $columnName;
        }
      } else {
        $logTableSpec[$tableName]['exceptions'] = $columnNames;
      }
    }
  }
}
