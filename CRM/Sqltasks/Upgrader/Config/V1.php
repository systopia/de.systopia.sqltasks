<?php


/**
 * Configuration upgrader for converting from format 1 to 2
 */
class CRM_Sqltasks_Upgrader_Config_V1 {

  /**
   * @var array task configuration
   */
  private $config;

  public function __construct(array $config) {
    $this->config = $config;
  }

  protected function convertConfig() {
    $newConfig = ['version' => 2, 'actions' => []];

    // always start with a RunSQL action filled with main_sql
    $newConfig['actions'][] = [
      'type'    => 'CRM_Sqltasks_Action_RunSQL',
      'script'  => $this->config['main_sql'],
      'enabled' => !empty($this->config['main_sql']),
    ];
    // available config prefixes, using fixed order from V1
    $prefixToTypeList = [
      'segmentation_assign' => 'CRM_Sqltasks_Action_SegmentationAssign',
      'activity'            => 'CRM_Sqltasks_Action_CreateActivity',
      'api'                 => 'CRM_Sqltasks_Action_APICall',
      'csv'                 => 'CRM_Sqltasks_Action_CSVExport',
      'tag'                 => 'CRM_Sqltasks_Action_SyncTag',
      'group'               => 'CRM_Sqltasks_Action_SyncGroup',
      'segmentation_export' => 'CRM_Sqltasks_Action_SegmentationExport',
      'task'                => 'CRM_Sqltasks_Action_CallTask',
      'success'             => 'CRM_Sqltasks_Action_SuccessHandler',
      'error'               => 'CRM_Sqltasks_Action_ErrorHandler',
    ];

    /* V2 sample:
      {
        "version": 2,
        "actions": [
          {
            "type": "CRM_Sqltasks_Action_CreateActivity",
            "enabled": true,
            ...
          }
        ]
      }
    */

    // iterate over all prefixes
    foreach ($prefixToTypeList as $prefix => $type) {
      if (!$type::isSupported()) {
        // don't create actions that are not supported
        continue;
      }
      if ($prefix == 'success') {
        // special case: post_sql should run before the success handler
        $newConfig['actions'][] = [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'script'  => $this->config['post_sql'],
          'enabled' => !empty($this->config['post_sql']),
        ];
      }
      $action = ['type' => $type];
      // iterate over all config keys and copy those starting with the prefix
      foreach ($this->config['config'] as $key => $value) {
        if (strpos($key, $prefix . '_') === 0) {
          $itemName = preg_replace("/^{$prefix}_/", '', $key);
          $action[$itemName] = $value;
        }
      }
      $newConfig['actions'][] = $action;
    }

    $scheduleDetails = [
      'scheduled_month', 'scheduled_weekday', 'scheduled_day', 'scheduled_hour',
      'scheduled_minute'
    ];
    foreach ($scheduleDetails as $key) {
      if (!empty($this->config['config'][$key])) {
        $newConfig[$key] = $this->config['config'][$key];
      }
    }

    return $newConfig;
  }

  public function convert() {
    $newConfig = $this->config;
    $newConfig['config'] = $this->convertConfig();
    // main_sql and post_sql were converted to actions
    unset($newConfig['main_sql']);
    unset($newConfig['post_sql']);
    return $newConfig;
  }

}
