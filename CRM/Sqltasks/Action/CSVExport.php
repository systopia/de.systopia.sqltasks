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
 * This actions allows you to synchronise
 *  a resulting contact set with a group
 *
 */
class CRM_Sqltasks_Action_CSVExport extends CRM_Sqltasks_Action {

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
  public function getDefaultOrder() {
    return 400;
  }

  /**
   * Build the configuration UI
   */
  public function buildForm(&$form) {
    parent::buildForm($form);

    $form->add(
      'text',
      $this->getID() . '_table',
      E::ts('Export Table'),
      ['style' => 'font-family: monospace, monospace !important']
    );

    $form->add(
      'select',
      $this->getID() . '_encoding',
      E::ts('File Encoding'),
      static::getEncodingOptions(),
      FALSE,
      ['class' => 'crm-select2 huge']
    );

    $form->add(
      'select',
      $this->getID() . '_delimiter',
      E::ts('Delimiter'),
      static::getDelimiterOptions()
    );

    $form->add(
      'text',
      $this->getID() . '_delimiter_other',
      E::ts('Other delimiter'),
      ['style' => 'width: 50px; font-family: monospace, monospace !important']
    );

    $form->add(
      'textarea',
      $this->getID() . '_headers',
      E::ts('Columns'),
      array('rows' => 8, 'cols' => 40, 'style' => 'font-family: monospace, monospace !important')
    );


    $form->add(
      'checkbox',
      $this->getID() . '_zip',
      E::ts('ZIP File')
    );

    $form->add(
      'text',
      $this->getID() . '_filename',
      E::ts('File Name'),
      array('class' => 'huge', 'style' => 'font-family: monospace, monospace !important')
    );

    $form->add(
      'text',
      $this->getID() . '_path',
      E::ts('File Path'),
      array('class' => 'huge', 'style' => 'font-family: monospace, monospace !important')
    );

    $form->add(
      'text',
      $this->getID() . '_email',
      E::ts('Email to'),
      array('class' => 'huge')
    );

    $form->add(
      'checkbox',
      $this->getID() . '_downloadURL',
      E::ts('Send URL to download file instead of attachment')
    );

    $form->add(
      'select',
      $this->getID() . '_email_template',
      E::ts('Email Template'),
      $this->getAllTemplates(),
      FALSE,
      ['class' => 'crm-select2 huge']
    );

    $form->add(
      'text',
      $this->getID() . '_upload',
      E::ts('Upload to'),
      array('class' => 'huge', 'style' => 'font-family: monospace, monospace !important')
    );

    $form->add(
      'checkbox',
      $this->getID() . '_discard_empty',
      E::ts('Discard empty file?')
    );
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
   * Parse the credentials
   * @return FALSE if nothing is entered, 'ERROR' if it cannot be parsed
   */
  protected function getCredentials() {
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
   * get the table with the contact_id column
   */
  protected function getExportTable() {
    $table_name = $this->getConfigValue('table');
    $this->resolveTableToken($table_name);
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
    $out = fopen($filepath, 'w');

    // then: run the query
    $export_table = $this->getExportTable();
    $column_specs = $this->getColumnSpecs();
    $delimiter = $this->getConfigValue('delimiter');
    $delimiter_other = $this->getConfigValue('delimiter_other');
    if(empty($delimiter) && !empty($delimiter_other)){
      $delimiter = $delimiter_other;
    }

    $encoding  = $this->getConfigValue('encoding');

    // parse specs
    $headers = array();
    $columns = array();
    foreach ($column_specs as $column_spec) {
      $headers[] = $column_spec[0];
      $columns[] = $column_spec[1];
    }

    // write headers
    $this->writeLine($out, $headers, $delimiter, $encoding);

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
      $this->writeLine($out, $record, $delimiter, $encoding);
      $count++;
    }
    $query->free();
    fclose($out);
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
    $this->task->addGeneratedFile(
        E::ts("%1 CSV Export", [1 => $this->task->getAttribute('name')]),
        $filename,
        $filepath,
        $mime_type,
        $config_offer_link);

    // 2) EMAIL
    $config_email = $this->getConfigValue('email');
    $config_email_template = $this->getConfigValue('email_template');
    if (!empty($config_email) && !empty($config_email_template)) {
      // add all the variables
      $email_list = $this->getConfigValue('email');
      list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();
      $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();

      // and send the template via email
      $email = array(
          'id'        => $this->getConfigValue('email_template'),
          'to_email'  => $this->getConfigValue('email'),
          'from'      => "SQL Tasks <{$domainEmailAddress}>",
          'reply_to'  => "do-not-reply@{$emailDomain}",
          'contactId' => CRM_Core_Session::getLoggedInContactID() // sluc: if contactId param is empty, it won't get into hook_civicrm_tokenValues()
        );

      // add file as attachment or setup URL token
      if(!$config_offer_link){
        $attachment = array('fullPath'  => $filepath,
                            'mime_type' => $mime_type,
                            'cleanName' => basename($filepath));
        $email['attachments'] = [$attachment];
      }

      civicrm_api3('MessageTemplate', 'send', $email);
      $this->log("Sent file to '{$email_list}'");
    }

    // 3) UPLOAD
    if ($this->getConfigValue('upload')) {
      $credentials = $this->getCredentials();
      if ($credentials && $credentials != 'ERROR') {
        // connect
        require_once('Net/SFTP.php');
        define('NET_SFTP_LOGGING', NET_SFTP_LOG_SIMPLE);
        $sftp = new Net_SFTP($credentials['host']);
        if (!$sftp->login($credentials['user'], $credentials['password'])) {
          throw new Exception("Login to {$credentials['user']}@{$credentials['host']} Failed", 1);
        }

        // upload
        $target_file = $credentials['remote_path'] . '/' . $filename;
        if (!$sftp->put($target_file, $filepath, NET_SFTP_LOCAL_FILE)) {
          throw new Exception("Upload to {$credentials['user']}@{$credentials['host']} failed: " . $sftp->getSFTPLog(), 1);
        }

        $this->log("Uploaded file '{$filename}' to {$credentials['host']}/{$target_file}");

      } else {
        throw new Exception("Upload failed, couldn't parse credentials", 1);
      }
    }
  }

  /**
   *
   * @todo: configure more of fputcsv ( resource $handle , array $fields [, string $delimiter = "," [, string $enclosure = '"' [, string $escape_char = "\" ]]] )
   */
  protected function writeLine($out, $record, $delimiter, $encoding = NULL) {
    if ($encoding) {
      $encoded_record = array();
      foreach ($record as $value) {
        $encoded_record[] = mb_convert_encoding($value, $encoding);
      }
      fputcsv($out, $encoded_record, $delimiter);
    } else {
      fputcsv($out, $record, $delimiter);
    }
  }
}
