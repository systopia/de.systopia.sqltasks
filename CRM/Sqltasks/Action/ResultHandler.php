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
 * This is a generic handler to communicate the completion of
 * a SQL task. Currently there are two handlers in use:
 *  'success' will be triggered when the task was completed successfully
 *  'error'   will be triggered if an error occurs during execution
 *
 */
abstract class CRM_Sqltasks_Action_ResultHandler extends CRM_Sqltasks_Action {

  protected $id;
  protected $name;

  public function __construct(CRM_Sqltasks_Task $task, array $config, $id, $name) {
    parent::__construct($task, $config);
    $this->id   = $id;
    $this->name = $name;
  }

  /**
   * Get identifier string
   */
  public function getID() {
    return $this->id;
  }

  /**
   * Get a human readable name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Report this class as a handler
   */
  public function isResultHandler() {
    return TRUE;
  }

  /**
   * get the template file for the configuration UI
   */
  public function getFormTemplate() {
    switch ($this->id) {
      case 'error':
        return 'CRM/Sqltasks/Action/ErrorHandler.tpl';

      default:
      case 'success':
        return 'CRM/Sqltasks/Action/SuccessHandler.tpl';
    }
  }


  /**
   * Build the configuration UI
   */
  public function buildForm(&$form) {
    parent::buildForm($form);

    if ($this->id == 'success') {
      $form->add(
        'checkbox',
        $this->getID() . '_always',
        E::ts('Execute always')
      );
    }

    $form->add(
      'text',
      $this->getID() . '_table',
      E::ts('User Error Table'),
      ['style' => 'font-family: monospace, monospace !important']
    );

    $form->add(
      'checkbox',
      $this->getID() . '_drop_table',
      E::ts('Drop Error Table')
    );

    $form->add(
      'text',
      $this->getID() . '_email',
      E::ts('Email to'),
      array('class' => 'huge')
    );

    $form->add(
      'select',
      $this->getID() . '_email_template',
      E::ts('Email Template'),
      $this->getAllTemplates()
    );

    $form->add(
      'checkbox',
      $this->getID() . '_attach_log',
      E::ts('Attach Log')
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
   * Check if this action is configured correctly
   */
  public function checkConfiguration() {
    parent::checkConfiguration();

    // nothing to do here...
  }

  /**
   * Should the success handler run?
   */
  public function shouldSuccessHandlerRun($actions) {
    // is there errors?
    if ($this->shouldErrorHandlerRun($actions)) {
      return FALSE;
    }

    // check if we always want the success handler to run
    if ($this->getConfigValue('always')) {
      return TRUE;
    }

    // otherwise we want to make sure that at least one
    //  action has done something
    foreach ($actions as $action) {
      if (!$action->isResultHandler()) {
        if ($action->hasExecuted()) {
          return TRUE;
        }
      }
    }

    // if none of the above is TRUE, we shouldn't execute
    return FALSE;
  }

  /**
   * Should the error handler run?
   */
  public function shouldErrorHandlerRun($actions) {
    // is there recorded errors?
    if ($this->task->hasExecutionErrors()) {
      return TRUE;
    }

    // is there user-generated errors?
    $errors = $this->getErrorsFromTable();
    if (!empty($errors)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * RUN this action
   */
  public function execute() {
    // check if we need to be executed
    $should_run = FALSE;
    if ($this->id == 'success') {
      $should_run = $this->shouldSuccessHandlerRun($this->context['actions']);
    } elseif ($this->id == 'error') {
      $should_run = $this->shouldErrorHandlerRun($this->context['actions']);
    }
    if (!$should_run) {
      $this->log("Skipping Success Handler, actions didn't do anything");
      return;
    }

    // inject user reported errors
    if ($this->id == 'error') {
      $errors = $this->getErrorsFromTable();
      foreach ($errors as $error) {
        $this->log("Reported error: " . $error);
      }
    }

    // now drop table if requested
    $this->dropErrorTable();

    // send out email
    $config_email = $this->getConfigValue('email');
    $config_email_template = $this->getConfigValue('email_template');
    if (!empty($config_email) && !empty($config_email_template)) {
      // compile email
      $email_list = $this->getConfigValue('email');
      list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();
      $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();
      $email = array(
        'id'              => $this->getConfigValue('email_template'),
        // 'to_name'         => $this->getConfigValue('email'),
        'to_email'        => $this->getConfigValue('email'),
        'from'            => "SQL Tasks <{$domainEmailAddress}>",
        'reply_to'        => "do-not-reply@{$emailDomain}",
        );

      // attach the log
      $attach_log = $this->getConfigValue('attach_log');
      if ($attach_log) {
        // write out log
        $logfile = $this->task->writeLogfile();

        // attach it
        $email['attachments'][] = array('fullPath'  => $logfile,
                                        'mime_type' => 'application/zip',
                                        'cleanName' => $this->task->getAttribute('name') . '-execution.log');
      }

      // and send the template via email
      civicrm_api3('MessageTemplate', 'send', $email);
      $this->log("Sent {$this->id} message to '{$email_list}'");
    }
  }

  /**
   * this is a handler function, where
   * you can set a table with error messages
   */
  protected function getErrorsFromTable() {
    // get the error table
    $error_table = $this->getErrorTable();
    if (!$error_table) {
      // it's not properly set
      return array();
    }

    // find error_message column
    $existing_column = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `{$error_table}` LIKE 'error_message';");
    if (!$existing_column) {
      return array();
    }

    // finally, return the errors
    $errors = array();
    $query = CRM_Core_DAO::executeQuery("SELECT `error_message` FROM `{$error_table}`;");
    while ($query->fetch()) {
      if (!empty($query->error_message)) {
        $errors[] = $query->error_message;
      }
    }

    return $errors;
  }

  /**
   * Will drop the error table if the setting is activated
   */
  protected function dropErrorTable() {
    $drop_table = $this->getConfigValue('drop_table');
    if ($drop_table) {
      $error_table = $this->getErrorTable();
      if ($error_table) {
        CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `{$error_table}`;");
        CRM_Core_DAO::executeQuery("DROP VIEW IF EXISTS `{$error_table}`;");
      }
    }
  }

  /**
   * Return the error table if
   *  - the setting is set
   *  - the table exists
   */
  protected function getErrorTable() {
    // see if a table is set
    $error_table = $this->getConfigValue('table');
    if (empty($error_table)) {
      return NULL;
    }

    $this->resolveTableToken($error_table);

    // make sure the table exists
    $error_table = trim($error_table);
    $existing_table = CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '{$error_table}';");
    if (!$existing_table) {
      return NULL;
    }

    return $error_table;
  }


}
