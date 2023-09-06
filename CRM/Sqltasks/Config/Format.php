<?php

/**
 * Utility class for the task configuration format
 */
class CRM_Sqltasks_Config_Format {

  const LEGACY_FORMAT_HEADER = '/^\/\* ##### SQLTASK VERSION 0.9 ###########\n/';
  const LEGACY_FORMAT_MAIN_HEADER = "\n*/ ############ MAIN SQL ###############\n";
  const LEGACY_FORMAT_POST_HEADER = "\n-- ############ POST SQL ###############\n";

  const SCRIPT_SENTINEL = "##### EDITS BELOW THIS LINE WILL BE IGNORED #####\n";

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
   * @param string|array $config task attributes and configuration
   *
   * @return array upgraded task configuration
   * @throws \Exception
   */
  public static function toLatest($config) {
    if (!is_array($config)) {
      if (preg_match(self::LEGACY_FORMAT_HEADER, $config, $match)
        && strstr($config, self::LEGACY_FORMAT_MAIN_HEADER)
        && strstr($config, self::LEGACY_FORMAT_POST_HEADER)) {
        // legacy v1-like format with appended SQL and commented JSON
        $config = self::extractConfigFromLegacyFormat($config);
      }
      elseif ($config[0] == '{' && strstr($config, self::SCRIPT_SENTINEL)) {

        // V2 with appended scripts
        $config = self::extractConfigFromV2WithAppendedScripts($config);
      }
      else {
        $config = json_decode($config, TRUE);
        if (is_null($config)) {
          throw new Exception( 'Invalid task configuration provided: Invalid JSON');
        }
      }
    }
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

  /**
   * Extract V2 configuration from a JSON file with appended scripts
   *
   * This format is identified by a leading "{" later followed by this line:
   * ##### EDITS BELOW THIS LINE WILL BE IGNORED #####
   *
   * @param $config
   *
   * @return mixed
   * @throws \Exception
   */
  public static function extractConfigFromV2WithAppendedScripts($config) {
    $jsonPart = substr($config, 0, strpos($config, self::SCRIPT_SENTINEL));
    $config = json_decode($jsonPart, TRUE);
    if (is_null($config)) {
      throw new Exception( 'Invalid task configuration provided: Invalid JSON');
    }
    return $config;
  }

  /**
   * Import from "legacy" / systopia-style task format
   *
   * This format is identified by the following header:
   * /* ##### SQLTASK VERSION 0.9 ###########
   *
   * @param $config
   *
   * @return array
   */
  public static function extractConfigFromLegacyFormat($config) {
    preg_match(self::LEGACY_FORMAT_HEADER, $config, $match);
    $start_main = strpos($config, self::LEGACY_FORMAT_MAIN_HEADER);
    $start_post = strpos($config, self::LEGACY_FORMAT_POST_HEADER);
    $len_header = strlen($match[0]);
    $len_main = strlen(self::LEGACY_FORMAT_MAIN_HEADER);
    $len_post = strlen(self::LEGACY_FORMAT_POST_HEADER);

    $data = substr($config, $len_header, ($start_main - $len_header));
    $main_sql = substr($config, ($start_main + $len_main), ($start_post - $start_main - $len_main));
    $post_sql = substr($config, ($start_post + $len_post));

    return array_merge(json_decode($data, TRUE), [
      'main_sql' => $main_sql,
      'post_sql' => $post_sql,
    ]);
  }

}
