<?php

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
  'sqltasks_is_dispatcher_disabled' => [
    'name'            => 'sqltasks_is_dispatcher_disabled',
    'type'            => 'Boolean',
    'html_type'       => 'text',
    'default'         => '0',
    'title'           => ts('Is sqltasks dispatcher disabled?'),
    'is_domain'       => 1,
    'is_contact'      => 0,
    'description'     => ts('Is sqltasks dispatcher disabled?'),
  ],
  'sqltasks_max_fails_number' => [
    'name'        => 'sqltasks_max_fails_number',
    'type'        => 'String',
    'default'     => '0',
    'html_type'   => 'text',
    'title'       => ts('Max fails number'),
    'is_domain'   => 1,
    'is_contact'  => 0,
    'description' => ts('Max fails number for sqltask executions. Ex: A value of 5 should stop execution after 5 failed tasks, 0 - never stop execution.'),
  ],
];
