<?php
/*
 * Copyright (C) 2023 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use CRM_Sqltasks_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation__automation',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'domain_id' => 'current_domain',
        'label' => E::ts('Automation'),
        'name' => 'automation',
        'url' => NULL,
        'icon' => NULL,
        'permission' => [
          'administer CiviCRM',
        ],
        'permission_operator' => 'OR',
        'parent_id.name' => 'Administer',
        'is_active' => TRUE,
        'has_separator' => 0,
      ],
      'match' => ['name', 'parent_id'],
    ],
  ],
  [
    'name' => 'Navigation__sqltasks_manage',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'domain_id' => 'current_domain',
        'label' => E::ts('SQL Tasks'),
        'name' => 'sqltasks_manage',
        'url' => 'civicrm/a/#/sqltasks/manage',
        'icon' => NULL,
        'permission' => [
          'administer CiviCRM',
        ],
        'permission_operator' => 'OR',
        'parent_id.name' => 'automation',
        'is_active' => TRUE,
        'has_separator' => 0,
      ],
      'match' => ['name', 'parent_id'],
    ],
  ],
  [
    'name' => 'Navigation__sqltasks_global_token_manager',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'domain_id' => 'current_domain',
        'label' => E::ts('Global Token Manager'),
        'name' => 'global_token_manager',
        'url' => 'civicrm/sqltasks/global-token-manager',
        'icon' => NULL,
        'permission' => [
          'administer CiviCRM',
        ],
        'permission_operator' => 'OR',
        'parent_id.name' => 'sqltasks_manage',
        'is_active' => TRUE,
        'has_separator' => 0,
      ],
      'match' => ['name', 'parent_id'],
    ],
  ],
  [
    'name' => 'Navigation__sqltasks_templates',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'domain_id' => 'current_domain',
        'label' => E::ts('Task Templates'),
        'name' => 'templates',
        'url' => 'civicrm/sqltasks/templates',
        'icon' => NULL,
        'permission' => [
          'administer CiviCRM',
        ],
        'permission_operator' => 'OR',
        'parent_id.name' => 'sqltasks_manage',
        'is_active' => TRUE,
        'has_separator' => 0,
      ],
      'match' => ['name', 'parent_id'],
    ],
  ],
  [
    'name' => 'Navigation__sqltasks_export_all',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'domain_id' => 'current_domain',
        'label' => E::ts('Export All Tasks'),
        'name' => 'export_all',
        'url' => 'civicrm/sqltasks/export',
        'icon' => NULL,
        'permission' => [
          'administer CiviCRM',
        ],
        'permission_operator' => 'OR',
        'parent_id.name' => 'sqltasks_manage',
        'is_active' => TRUE,
        'has_separator' => 0,
      ],
      'match' => ['name', 'parent_id'],
    ],
  ],
];
