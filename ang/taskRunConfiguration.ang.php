<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
return [
  'requires' => ['ngRoute'],
  'js' =>
    [
      0 => 'ang/taskRunConfiguration.js',
    ],
  'css' => [
    0 => 'css/taskRunConfiguration.css',
  ],
  'partials' => [
    0 => 'ang/taskRunConfiguration',
  ],
  'settings' => [],
];
