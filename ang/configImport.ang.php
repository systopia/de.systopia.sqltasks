<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
return [
  'requires' => ['ngRoute'],
  'js' => [
    0 => 'ang/*.js',
  ],
  'css' => [
    0 => 'css/sqlTaskGeneral.css',
  ],
  'partials' => [
    0 => 'ang/configImport',
  ],
  'settings' => [],
];
