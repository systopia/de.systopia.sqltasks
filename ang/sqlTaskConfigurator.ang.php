<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
return [
  'requires' => ['ngRoute', 'ui.sortable'],
  'js' => [ 
    0 => 'ang/sqlTaskConfigurator.js',
    1 => 'ang/actions/*.js',
  ],
  'css' => [
    0 => 'css/sqlTaskConfigurator.css',
  ],
  'partials' => [
    0 => 'ang/sqlTaskConfigurator',
    1 => 'ang/actions',
    2 => 'ang/inputs',
  ],
  'settings' => [],
];