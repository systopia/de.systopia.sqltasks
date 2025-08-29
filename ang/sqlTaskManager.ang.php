<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
return [
  'requires' => ['ngRoute', "ui.sortable"],
  'js' =>
    [
      0 => 'ang/sqlTaskManager.js',
    ],
  'css' => [
    0 => 'css/sqlTaskManager.css',
    1 => 'css/sqlTaskGeneral.css',
  ],
  'partials' => [
    0 => 'ang/sqlTaskManager',
  ],
  'settings' => [],
];
