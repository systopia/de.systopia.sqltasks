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
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function sqltasks_civicrm_install() {
  _sqltasks_civix_civicrm_install();
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
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function sqltasks_civicrm_navigationMenu(&$menu) {
  _sqltasks_civix_insert_navigation_menu($menu, 'Contacts', array(
      'label'      => E::ts('My Tasks'),
      'name'       => 'sqltasks_mytasks',
      'url'        => 'civicrm/sqltasks/mytasks',
      'permission' => 'access CiviCRM',
      'operator'   => 'OR',
      'separator'  => 0,
  ));

  // also add to Automation section
  if (!_sqltasks_menu_exists($menu, 'Administer/automation')) {
    _sqltasks_civix_insert_navigation_menu($menu, 'Administer', [
        'label'      => E::ts('Automation'),
        'name'       => 'automation',
        'url'        => NULL,
        'permission' => 'administer CiviCRM',
        'operator'   => NULL,
        'separator'  => 0,
    ]);
  }
  _sqltasks_add_admin_items($menu, 'Administer/System Settings');
  _sqltasks_add_admin_items($menu, 'Administer/automation');

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
 * Checks whether a navigation menu item exists.
 *  (copied from form processor, code by Jaap)
 *
 * @param array $menu - menu hierarchy
 * @param string $path - path to parent of this item, e.g. 'my_extension/submenu'
 *    'Mailing', or 'Administer/System Settings'
 * @return bool
 */
function _sqltasks_menu_exists(&$menu, $path) {
  // Find an recurse into the next level down
  $found = FALSE;
  $path = explode('/', $path);
  $first = array_shift($path);
  foreach ($menu as $key => &$entry) {
    if ($entry['attributes']['name'] == $first) {
      if (empty($path)) {
        return true;
      }
      $found = _sqltasks_menu_exists($entry['child'], implode('/', $path));
      if ($found) {
        return true;
      }
    }
  }
  return $found;
}

function _sqltasks_add_admin_items(&$menu, $path) {
  _sqltasks_civix_insert_navigation_menu($menu, $path, [
    'label'      => E::ts('SQL Tasks'),
    'name'       => 'sqltasks_manage',
    'url'        => 'civicrm/a/#/sqltasks/manage',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 0,
  ]);
  _sqltasks_civix_insert_navigation_menu($menu, $path . '/sqltasks_manage', array(
    'label'      => E::ts('Global Token Manager'),
    'name'       => 'global_token_manager',
    'url'        => 'civicrm/sqltasks/global-token-manager',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 0,
  ));
  _sqltasks_civix_insert_navigation_menu($menu, $path . '/sqltasks_manage', array(
    'label'      => E::ts('Task Templates'),
    'name'       => 'templates',
    'url'        => 'civicrm/sqltasks/templates',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 0,
  ));
  _sqltasks_civix_insert_navigation_menu($menu, $path . '/sqltasks_manage', array(
    'label'      => E::ts('Export All Tasks'),
    'name'       => 'export_all',
    'url'        => 'civicrm/sqltasks/export',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 0,
  ));
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

function sqltasks_civicrm_container(ContainerBuilder $container) {
  $container->addCompilerPass(new Civi\Sqltasks\CompilerPass());
}
