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
    'title'           => ts('Is SQL Task Dispatcher disabled?'),
    'is_domain'       => 1,
    'is_contact'      => 0,
    'description'     => ts('Disables automatic task execution of scheduled tasks via the dispatcher'),
  ],
  'sqltasks_max_fails_number' => [
    'name'        => 'sqltasks_max_fails_number',
    'type'        => 'String',
    'default'     => '0',
    'html_type'   => 'text',
    'title'       => ts('Maximum number of SQL Task Fails'),
    'is_domain'   => 1,
    'is_contact'  => 0,
    'description' => ts('Maximum number of fails before dispatcher is disabled. Defaults to 0 (no limit)'),
  ],
];
