<?php

/**
 * Utility class for the task configuration format
 */
class CRM_Sqltasks_Config_Format {

  /**
   * Current version of the task configuration format
   */
  const CURRENT = 2;

  /**
   * Determine the version of a task configuration
   *
   * @param array $config task configuration
   *
   * @return int
   * @throws \Exception
   */
  public static function getVersion(array $config) {
    // V1 did not have an explicit version field, so we're assuming this is V1
    if (!array_key_exists('version', $config)) {
      return 1;
    }

    return $config['version'];
  }

  /**
   * Check whether a task configuration uses the current format version
   *
   * @param array $config task configuration
   *
   * @return bool
   * @throws \Exception
   */
  public static function isLatest(array $config) {
    if (self::getVersion($config) == self::CURRENT) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Upgrade a task configuration to the latest version
   *
   * @param array $config task attributes and configuration
   *
   * @return array upgraded task configuration
   * @throws \Exception
   */
  public static function toLatest(array $config) {
    $version = self::getVersion($config['config']);
    if ($version > self::CURRENT) {
      throw new Exception( 'Incompatible task configuration version: ' . $config['version'] .
                          '. Please upgrade ' . CRM_Sqltasks_ExtensionUtil::LONG_NAME . ' to use this configuration.');
    }

    $upgrader = NULL;
    switch ($version) {
      case 1:
        $upgrader = new CRM_Sqltasks_Upgrader_Config_V1($config);
        break;
    }

    if (is_null($upgrader)) {
      // config is already on latest version
      return $config;
    }
    else {
      // run config through the upgrader
      return self::toLatest($upgrader->convert());
    }
  }

}
