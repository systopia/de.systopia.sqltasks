<?php

/**
 * Trait for Action classes that upload file with sftp
 */
trait CRM_Sqltasks_Action_SftpTrait {

  /**
   * Parse the credentials
   * @return FALSE if nothing is entered, 'ERROR' if it cannot be parsed
   */
  public function getCredentials() {
    $credentials = $this->getConfigValue('upload');
    if (!empty($credentials)) {
      $credentials = trim($credentials);
      if (preg_match('#^sftp:\/\/(?<user>[^:]+):(?<password>[^@]+)@(?<host>[\w.-]+)(?<remote_path>\/[\/\w_-]+)$#', $credentials, $match)) {
        return $match;
      } else {
        return 'ERROR';
      }
    }
    return FALSE;
  }

  /**
   * Create sftp and upload given file
   *
   * @param string $filename
   * @param string $filepath
   * @return void
   * @throws Exception
   */
  public function uploadSftp(string $filename, string $filepath) {
    $credentials = $this->getCredentials();
    if ($credentials && $credentials != 'ERROR') {
      // connect
      if (class_exists(\phpseclib3\Net\SFTP::class)) {
        $sftpClass = \phpseclib3\Net\SFTP::class;
        $sftp = new $sftpClass($credentials['host']);
        $mode = $sftpClass::SOURCE_LOCAL_FILE;
        if (!defined('NET_SFTP_LOGGING') && defined($sftpClass . '::LOG_SIMPLE')) {
          define('NET_SFTP_LOGGING', constant($sftpClass . '::LOG_SIMPLE'));
        }
      }
      elseif (class_exists(\phpseclib\Net\SFTP::class)) {
        $sftpClass = \phpseclib\Net\SFTP::class;
        $sftp = new $sftpClass($credentials['host']);
        $mode = $sftpClass::SOURCE_LOCAL_FILE;
        if (!defined('NET_SFTP_LOGGING') && defined($sftpClass . '::LOG_SIMPLE')) {
          define('NET_SFTP_LOGGING', constant($sftpClass . '::LOG_SIMPLE'));
        }
      }
      else {
        // legacy phpseclib v1 (PEAR-style)
        require_once('Net/SFTP.php');
        $sftp = new Net_SFTP($credentials['host']);
        $mode = NET_SFTP_LOCAL_FILE;
        if (!defined('NET_SFTP_LOGGING') && defined('NET_SFTP_LOG_SIMPLE')) {
          define('NET_SFTP_LOGGING', NET_SFTP_LOG_SIMPLE);
        }
      }
      if (!$sftp->login($credentials['user'], $credentials['password'])) {
        throw new Exception("Login to {$credentials['user']}@{$credentials['host']} Failed", 1);
      }

      // upload
      $target_file = $credentials['remote_path'] . '/' . $filename;
      if (!$sftp->put($target_file, $filepath, $mode)) {
        throw new Exception("Upload to {$credentials['user']}@{$credentials['host']} failed: " . $sftp->getSFTPLog(), 1);
      }

      $this->log("Uploaded file '{$filename}' to {$credentials['host']}/{$target_file}");

    } else {
      throw new Exception("Upload failed, couldn't parse credentials", 1);
    }
  }

  /**
   * Retry to call a function (sftp upload) to given maximum retries
   *
   * @param callable $callable
   * @param int $maxRetries
   * @param int $initialWait
   * @param array $expectedErrors
   * @param int $exponent
   * @return mixed
   * @throws Exception
   */
  function retrySftp(callable $callable, int $maxRetries = 5, int $initialWait = 1, array $expectedErrors = [Exception::class], int $exponent = 2)
  {
    try {
      return call_user_func($callable);
    } catch (Exception $e) {
      // get whole inheritance chain
      $errors = class_parents($e);
      array_push($errors, get_class($e));

      // if unexpected, re-throw
      if (!array_intersect($errors, $expectedErrors)) {
        throw $e;
      }

      // exponential backoff
      if ((int)$maxRetries > 0) {
        $this->log("Error during SFTP Upload (retrying): " . $e->getMessage(), 'error');

        usleep($initialWait * 1E6);
        return $this->retrySftp($callable, $maxRetries - 1, $initialWait * $exponent, $expectedErrors, $exponent);
      }

      // max retries reached
      throw $e;
    }
  }
}
