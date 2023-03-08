<?php

use Civi\Utils\Settings;

return [
  'sqltasks_global_tokens' => [
    'name'        => 'sqltasks_global_tokens',
    'type'        => 'Array',
    'default'     => [],
    'html_type'   => 'text',
    'title'       => ts('SQL Tasks global tokens'),
    'is_domain'   => 1,
    'is_contact'  => 0,
    'description' => ts('Array of global tokens for SQL Tasks which may be used using the {config.token_name} syntax.'),
  ],
  'sqltasks_default_template' => [
    'name'        => 'sqltasks_default_template',
    'type'        => 'String',
    'default'     => null,
    'html_type'   => 'text',
    'title'       => ts('SQL Tasks default template'),
    'is_domain'   => 1,
    'is_contact'  => 0,
    'description' => ts('Default configuration (ID) for new SQL Tasks.'),
  ],
  Settings::SQLTASKS_IS_DISPATCHER_DISABLED => [
    'name'            => Settings::SQLTASKS_IS_DISPATCHER_DISABLED,
    'type'            => 'Boolean',
    'html_type'       => 'text',
    'default'         => '0',
    'title'           => ts('Is sqltasks dispatcher disabled?'),
    'is_domain'       => 1,
    'is_contact'      => 0,
    'description'     => ts('Is sqltasks dispatcher disabled?'),
  ],
  Settings::SQLTASKS_MAX_FAILS_NUMBER => [
    'name'        => Settings::SQLTASKS_MAX_FAILS_NUMBER,
    'type'        => 'String',
    'default'     => '0',
    'html_type'   => 'text',
    'title'       => ts('Max fails number'),
    'is_domain'   => 1,
    'is_contact'  => 0,
    'description' => ts('Max fails number for sqltask executions. Ex: A value of 5 should stop execution after 5 failed tasks, 0 - never stop execution.'),
  ],
];
