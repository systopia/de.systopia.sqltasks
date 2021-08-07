<?php

/**
 * Utility functions
 */
class CRM_Sqltasks_Utils {

  public static function isSegmentationInstalled() {
    // TODO: cache? but be aware, unit tests need this uncached atm
    return civicrm_api3('Extension', 'getcount', [
      'full_name' => 'de.systopia.segmentation',
      'status'    => 'installed',
    ]) == 1;
  }

  public static function runSqlQuery($queryString) {
    $config = CRM_Core_Config::singleton();
    $dsn = $config->dsn;
    $errorScope = CRM_Core_TemporaryErrorScope::useException();

    if ($dsn === NULL) {
      $db = CRM_Core_DAO::getConnection();
    }
    else {
      require_once 'DB.php';
      $dsn = CRM_Utils_SQL::autoSwitchDSN($dsn);
      try {
        $db = DB::connect($dsn);
      }
      catch (Exception $e) {
        die("Cannot open $dsn: " . $e->getMessage());
      }
    }

    if (defined('SQLTASKS_CHARSET')) {
      // this is a hack to allow SQL Tasks developed with legacy charsets to run
      // by setting the SQLTASKS_CHARSET constant
      $db->query('SET NAMES ' . SQLTASKS_CHARSET);
    } else {
      $db->query('SET NAMES utf8mb4');
    }
    $transactionId = CRM_Utils_Type::escape(CRM_Utils_Request::id(), 'String');
    $db->query('SET @uniqueID = ' . "'$transactionId'");

    // get rid of comments starting with # and --

    $queryString = CRM_Utils_File::stripComments($queryString);

    $queries = preg_split('/;\s*$/m', $queryString);
    foreach ($queries as $query) {
      $query = trim($query);
      if (!empty($query)) {
        CRM_Core_Error::debug_query($query);
        $res = &$db->query($query);
      }
    }
  }

}
