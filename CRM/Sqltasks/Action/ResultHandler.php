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
class CRM_Sqltasks_Action_ResultHandler extends CRM_Sqltasks_Action {

  protected $id;
  protected $name;

  public function __construct($task, $id, $name) {
    parent::__construct($task);
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
   * generic execute implementation
   */
  public function execute() {
    // nothing to do here
  }

  /**
   * RUN this action
   */
  public function executeResultHandler($actions) {
    // check if we need to be executed
    if (   ($this->id == 'success' && !$this->task->hasExecutionErrors())
        || ($this->id == 'error'   && $this->task->hasExecutionErrors())) {

      // for success handler: check if anything was executed
      if ($this->id == 'success') {
        $execute_always = $this->getConfigValue('always');
        if (!$execute_always) {
          // only excute handler if something was executed
          $has_done_something = FALSE;
          foreach ($actions as $action) {
            if (!$action->isResultHandler()) {
              $has_done_something |= $action->has_executed;
            }
          }
          if (!$has_done_something) {
            $this->log("Nothing happened, so the success handler won't do anything either.");
            return;
          }
        }
      }

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
  }
}