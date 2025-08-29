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
use League\Csv\CharsetConverter;
use League\Csv\EncloseField;
use League\Csv\Writer;

/**
 * This actions allows you to synchronise
 *  a resulting contact set with a group
 *
 */
class CRM_Sqltasks_Action_CSVExport extends CRM_Sqltasks_Action {
  use CRM_Sqltasks_Action_EmailActionTrait;
  use CRM_Sqltasks_Action_SftpTrait;

  /**
   * Types of CSV field enclosure
   */
  const ENCLOSURE_NONE = "none";
  const ENCLOSURE_PARTIAL = "partial";
  const ENCLOSURE_FULL = "full";

  /**
   * Get identifier string
   */
  public function getID() {
    return 'csv';
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return E::ts('CSV Export');
  }

  /**
   * Get default template order
   *
   * @return int
   */
  public static function getDefaultOrder() {
    return 400;
  }

  /**
   * get all possible delimiters
   */
  public static function getDelimiterOptions() {
    return array(
      ';' => E::ts('Semicolon (;)'),
      ',' => E::ts('Comma (,)'),
      '|' => E::ts('Vertical bar (|)'),
      '' => E::ts('other'),
    );
  }

  /**
   * get a list of CSV field enclosure options
   */
  public static function getEnclosureModes () {
    return [self::ENCLOSURE_NONE, self::ENCLOSURE_PARTIAL, self::ENCLOSURE_FULL];
  }

  /**
   * get a list of eligible templates for the email
   */
  protected function getAllTemplates() {
    $template_options = array();
    $template_query = civicrm_api3('MessageTemplate', 'get', array(
      'is_active'    => 1,
      'return'       => 'id,msg_title',
      'option.limit' => 0));
    foreach ($template_query['values'] as $template) {
      $template_options[$template['id']] = $template['msg_title'];
    }
    return $template_options;
  }

  /**
   * Get all possible encodings
   */
  public static function getEncodingOptions() {
    $encodings = array();
    $mb_list = mb_list_encodings();
    foreach ($mb_list as $mb_encoding) {
      $encodings[$mb_encoding] = $mb_encoding;
    }
    return $encodings;
  }

  /**
   * get the table with the contact_id column
   */
  protected function getExportTable() {
    $table_name = $this->getConfigValue('table');
    return trim($table_name);
  }

  /**
   * get the preferred filename
   */
  protected function getFileName() {
    $file_name = $this->getConfigValue('filename');
    $file_name = trim($file_name);
    if (empty($file_name)) {
      $file_name = '{YmdHis}_export.csv';
    }

    // substitute tokens
    while (preg_match('/{(?P<token>.+)}/', $file_name, $match)) {
      $token = $match['token'];
      $value = date($token);
      $file_name = str_replace('{' . $match['token'] . '}', $value, $file_name);
    }

    return $file_name;
  }

  /**
   * get a list of (header, column) definitions
   */
  protected function getColumnSpecs() {
    $header2column = array();
    $columns_spec = trim($this->getConfigValue('headers'));
    $spec_lines = explode(PHP_EOL, $columns_spec);
    foreach ($spec_lines as $spec_line) {
      $separator_index = strpos($spec_line, '=');
      if ($separator_index > 0) {
        $header = trim(substr($spec_line, 0, $separator_index));
        $column = trim(substr($spec_line, $separator_index + 1));
        if (!empty($header) && !empty($column)) {
          $header2column[] = array($header, $column);
        }
      } else {
        // this line is ignored, it doesn't have the asdasd=asdasd form
      }
    }

    return $header2column;
  }

  /**
   * get the selected filepath
   */
  protected function getFilePath($filename = NULL) {
    $file_path = $this->getConfigValue('path');
    $file_path = trim($file_path);
    if(empty($file_path)){
      $file_path = CRM_Core_Config::singleton()->customFileUploadDir;
    }
    while (DIRECTORY_SEPARATOR == substr($file_path, strlen($file_path) - 1)) {
      $file_path = substr($file_path, 0, strlen($file_path) - 1);
    }

    // add the $filename if requested
    if ($filename) {
      $file_path .= DIRECTORY_SEPARATOR . $filename;
    }
    return trim($file_path);
  }

  /**
   * Check if this action is configured correctly
   */
  public function checkConfiguration() {
    parent::checkConfiguration();

    $export_table = $this->getExportTable();
    if (empty($export_table)) {
      throw new Exception("Export Table not configured.", 1);
    }

    // check if table exists
    $existing_table = CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '{$export_table}';");
    if (!$existing_table) {
      throw new Exception("Export Table '{$export_table}' doesn't exist.", 1);
    }

    // check file path
    $file_check = $this->getFilePath();
    if (!is_writeable($file_check)) {
      throw new Exception("Cannot export file to '{$file_check}'.", 1);
    }

    // check if there is at least one column
    $column_specs = $this->getColumnSpecs();
    if (empty($column_specs)) {
      throw new Exception("No valid column specifications found'.", 1);
    }
  }

  /**
   * RUN this action
   */
  public function execute() {
    $this->resetHasExecuted();

    // first: get filename, open stream
    $filename = $this->getFileName();
    $filepath = $this->getFilePath($filename);
    // if (!file_exists($filepath)) {
    //   throw new Exception("Cannot export file to '{$filepath}'.", 1);
    // }

    $out = Writer::createFromString("");

    // then: run the query
    $export_table = $this->getExportTable();
    $column_specs = $this->getColumnSpecs();

    // Set field delimiter
    $delimiter = $this->getConfigValue('delimiter');
    $delimiter_other = $this->getConfigValue('delimiter_other');

    if(empty($delimiter) && !empty($delimiter_other)){
      $delimiter = $delimiter_other;
    }

    $out->setDelimiter($delimiter);

    // Set output encoding
    $encConfig  = $this->getConfigValue('encoding');
    $encoding = empty($encConfig) ? "UTF-8" : $encConfig;
    CharsetConverter::addTo($out, "UTF-8", $encoding);

    // Set field enclosure
    $enclosureMode = $this->getConfigValue("enclosure_mode");

    if ($enclosureMode === self::ENCLOSURE_NONE) {
      $out->setEnclosure(chr(0));
    }

    if ($enclosureMode === self::ENCLOSURE_NONE || $enclosureMode === self::ENCLOSURE_FULL) {
      EncloseField::addTo($out, "\t\x1f");
    }

    // parse specs
    $headers = array();
    $columns = array();
    foreach ($column_specs as $column_spec) {
      $headers[] = $column_spec[0];
      $columns[] = $column_spec[1];
    }

    // write headers
    $out->insertOne($headers);

    // write the records
    $count = 0;
    $column_list = implode(',', $columns);
    // error_log("SELECT {$column_list} FROM {$export_table}");
    $excludeSql = '';
    if ($this->_columnExists($export_table, 'exclude')) {
      $excludeSql = 'WHERE (exclude IS NULL OR exclude != 1)';
      $this->log('Column "exclude" exists, might skip some rows');
    }
    $query = CRM_Core_DAO::executeQuery("SELECT {$column_list} FROM {$export_table} {$excludeSql}");
    while ($query->fetch()) {
      $this->setHasExecuted();
      $record = array();
      foreach ($column_specs as $column_spec) {
        $column = $column_spec[1];
        // TODO: formatting?
        $record[] = isset($query->$column) ? $query->$column : '';
      }
      $out->insertOne($record);
      $count++;
    }
    $query->free();

    $csvOutput = $out->getContent();

    if ($enclosureMode === self::ENCLOSURE_NONE) {
      mb_internal_encoding($encoding);
      $csvOutput = mb_ereg_replace(chr(0), "", $csvOutput);
      mb_internal_encoding("UTF-8");
    }

    file_put_contents($filepath, $csvOutput);
    $this->log("Written {$count} records to '{$filepath}'");

    // CONTINUE WITH EMPTY FILES?
    $discard_empty = $this->getConfigValue('discard_empty');
    if ($count == 0 && !empty($discard_empty)) {
      unlink($filepath);
      $this->log("Discarded empty file, no upload/email will succeed");
      return;
    }

    // POST PROCESSING
    // 1) ZIP
    if ($this->getConfigValue('zip')) {
      // zip the file
      $zip = new ZipArchive();
      $zipfile = $filepath . '.zip';
      if ($zip->open($zipfile, ZipArchive::CREATE)!==TRUE) {
        throw new Exception("Cannot open zipfile '{$zipfile}'", 1);
      }
      $zip->addFile($filepath, $filename);
      $zip->close();
      $filepath = $zipfile;
      $filename .= '.zip';
      $this->log("Zipped file into '{$filepath}'");
    }

    // store/register the generated file file
    $config_offer_link = $this->getConfigValue('downloadURL');
    $mime_type = $this->getConfigValue('zip') ? 'application/zip' : 'text/csv';

    $this->context['execution']->addGeneratedFile(
        E::ts("%1 CSV Export", [1 => $this->task->name]),
        $filename,
        $filepath,
        $mime_type,
        $config_offer_link);

    // 2) EMAIL
    $config_email = $this->getConfigValue('email');
    $config_email_template = $this->getConfigValue('email_template');
    if (!empty($config_email) && !empty($config_email_template)) {
      // send the template via email
      $email = [
          'id' => $this->getConfigValue('email_template'),
          'to_email' => $this->getConfigValue('email'),
      ];
      // add file as attachment or setup URL token
      if (!$config_offer_link) {
        $attachment = [
          'fullPath'  => $filepath,
          'mime_type' => $mime_type,
          'cleanName' => basename($filepath)
        ];
        $email['attachments'] = [$attachment];
      }
      $this->sendEmailMessage($email);
    }

    // 3) UPLOAD
    if ($this->getConfigValue('upload')) {
      $this->retrySftp(
        function() use ($filename, $filepath) {
          return $this->uploadSftp($filename, $filepath);
        },
        Civi::settings()->get("sqltasks_sftp_max_retries"),
        Civi::settings()->get("sqltasks_sftp_retry_initial_wait")
      );
    }
  }
}
