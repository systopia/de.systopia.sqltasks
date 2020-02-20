<?php
/*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Sqltasks_ExtensionUtil as E;

/**
 * Configurations
 */
class CRM_Sqltasks_Config {

  const SQLTASK_FILE_FORMAT_VERSION = '0.9';

  const SQLTASK_FILE_FORMAT_FILE_HEADER = "/* ##### SQLTASK VERSION " . self::SQLTASK_FILE_FORMAT_VERSION . " ###########\n";
  const SQLTASK_FILE_FORMAT_MAIN_HEADER = "\n*/ ############ MAIN SQL ###############\n";
  const SQLTASK_FILE_FORMAT_POST_HEADER = "\n-- ############ POST SQL ###############\n";

  const SQLTASK_FILE_FORMAT_FILE_HEADER_PREG = '/^\/\* ##### SQLTASK VERSION (?P<version>[0-9]+[.][0-9]+) ###########\n/';

  private static $singleton = NULL;

  protected $jobs = NULL;

  /**
   * get the config instance
   */
  public static function singleton() {
    if (self::$singleton === NULL) {
      self::$singleton = new CRM_Sqltasks_Config();
    }
    return self::$singleton;
  }

  /**
   * Install a scheduled job if there isn't one already
   */
  public static function installScheduledJob() {
    $config = self::singleton();
    $jobs = $config->getScheduledJobs();
    if (empty($jobs)) {
      // none found? create a new one
      civicrm_api3('Job', 'create', array(
        'api_entity'    => 'Sqltask',
        'api_action'    => 'execute',
        'run_frequency' => 'Always',
        'name'          => E::ts('Run SQL Tasks'),
        'description'   => E::ts('Triggers the SQL Task dispatcher, see: civicrm/sqltasks/manage'),
        'is_active'     => '0'));
    }
  }

  /**
   * get all scheduled jobs that trigger the dispatcher
   */
  public function getScheduledJobs() {
    if ($this->jobs === NULL) {
      // find all scheduled jobs calling Sqltask.execute
      $query = civicrm_api3('Job', 'get', array(
        'api_entity'   => 'Sqltask',
        'api_action'   => 'execute',
        'option.limit' => 0));
      $this->jobs = $query['values'];
    }
    return $this->jobs;
  }

  /**
   * Get the current dispatcher call frequency, as
   * derived from the scheduled job
   * @return 'Always', 'Hourly' or 'Daily' or NULL if not active
   */
  public function getCurrentDispatcherFrequency() {
    $frequency = NULL;
    $jobs = civicrm_api3('Job', 'get', array(
        'api_entity'    => 'Sqltask',
        'api_action'    => 'execute',
        'is_active'     => '1'));
    foreach ($jobs['values'] as $job) {
      switch ($job['run_frequency']) {
        case 'Always':
          $frequency = 'Always';
          break;

        case 'Hourly':
          if ($frequency == NULL || $frequency == 'Daily') {
            $frequency = 'Hourly';
          }
          break;

        case 'Daily':
          if ($frequency == NULL) {
            $frequency = 'Daily';
          }
          break;

        default:
          CRM_Core_Session::setStatus(E::ts('Unexpected run frequency: %1', array(1 => $job['run_frequency'])), E::ts('Error'), 'error');
          break;
      }
    }
    return $frequency;
  }
}