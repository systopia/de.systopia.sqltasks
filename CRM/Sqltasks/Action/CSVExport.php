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
   * Build the configuration UI
   */
  public function buildForm(&$form) {
    parent::buildForm($form);

    $form->add(
      'text',
      $this->getID() . '_table',
      E::ts('Export Table'),
      TRUE
    );

    $form->add(
      'select',
      $this->getID() . '_encoding',
      E::ts('File Encoding'),
      $this->getEncodingOptions()
    );

    $form->add(
      'select',
      $this->getID() . '_delimiter',
      E::ts('Delimiter'),
      $this->getDelimiterOptions()
    );

    $form->add(
      'textarea',
      $this->getID() . '_headers',
      E::ts('Columns'),
      array('rows' => 8, 'cols' => 40),
      FALSE
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
      TRUE
    );

    $form->add(
      'text',
      $this->getID() . '_path',
      E::ts('File Path'),
      TRUE
    );

    $form->add(
      'text',
      $this->getID() . '_email',
      E::ts('Email to'),
      TRUE
    );

    $form->add(
      'checkbox',
      $this->getID() . '_upload',
      E::ts('Upload')
    );

    // TODO: upload parameters
  }

  /**
   * get all possible delimiters
   */
  protected function getDelimiterOptions() {
    return array(
      ';' => E::ts('Semicolon (;)'),
      ',' => E::ts('Comma (,)')
        );
  }

  /**
   * get all possible encodings
   */
  protected function getEncodingOptions() {
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
    while (preg_match('/\{(?P<token>\.+)\}/', $file_name, $match)) {
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

    $existing_column = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$export_table}` LIKE 'contact_id';");
    if (!$existing_column) {
      throw new Exception("Export Table '{$export_table}' doesn't have a column 'contact_id'.", 1);
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
    $query = CRM_Core_DAO::executeQuery("SELECT {$column_list} FROM {$export_table}");
    while ($query->fetch()) {
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

    // POST PROCESSING
    if ($this->getConfigValue('delimiter')) {
      // zip the file
      $zip = new ZipArchive();
      $zipfile = $filepath . '.zip';
      if ($zip->open($zipfile, ZipArchive::CREATE)!==TRUE) {
        throw new Exception("Cannot open zipfile '{$zipfile}'", 1);
      }
      $zip->addFile($filepath, $filename);
      $zip->close();
      $filepath = $zipfile;
      $this->log("Zipped file into '{$filepath}'");
    }

    // 2) EMAIL
    // 3) UPLOAD
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