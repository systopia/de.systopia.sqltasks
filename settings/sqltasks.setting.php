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
  'sqltask_export_append_scripts' => [
    'name'        => 'sqltask_export_append_scripts',
    'type'        => 'Boolean',
    'default'     => TRUE,
    'html_type'   => 'checkbox',
    'title'       => ts('SQL Tasks Export: Append Scripts?'),
    'is_domain'   => 1,
    'is_contact'  => 0,
    'description' => ts('Should task export files include a read-only version of the included SQL and PHP scripts?'),
  ],
];
