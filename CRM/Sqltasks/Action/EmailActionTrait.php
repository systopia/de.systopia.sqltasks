<?php

declare(strict_types = 1);

/**
 * Trait for Action classes that send out email
 */
trait CRM_Sqltasks_Action_EmailActionTrait {

  public function sendEmailMessage(array $params) {
    [$domainEmailName, $domainEmailAddress] = CRM_Core_BAO_Domain::getNameAndEmail();

    // Describe the task and its execution, so that the message template can
    // name what it is reporting on. Anything the caller passes takes precedence.
    $params['tplParams'] = array_merge(
      $this->getTaskTemplateParams(),
      $params['tplParams'] ?? []
    );

    $templateParams = array_merge(
      [
        'id'        => $params['id'],
        'from'      => "SQL Tasks <{$domainEmailAddress}>",
        'contactId' => CRM_Core_Session::getLoggedInContactID(),
      ],
      $params
    );
    unset($templateParams['to_email']);
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();
    if (!empty($emailDomain)) {
      $templateParams['reply_to'] = "do-not-reply@{$emailDomain}";
    }

    $to_email = $params['to_email'];
    if (!is_array($to_email)) {
      // assume we've received a comma-separated list of recipients
      $to_email = explode(',', $to_email);
    }

    if (count($to_email) > 0) {
      $toEmail = '';
      $ccEmails = [];
      $emailNumber = 1;

      foreach ($to_email as $email) {
        if ($emailNumber === 1) {
          $toEmail = trim($email);
        }
        else {
          $ccEmails[] = trim($email);
        }
        $emailNumber++;
      }

      civicrm_api3('MessageTemplate', 'send', array_merge($templateParams, [
        'cc' => implode(',', $ccEmails),
        'to_email' => $toEmail,
      ]));

      $this->log("Sent {$this->getID()} message to '{$email}' with cc: " . implode(',', $ccEmails) . '.');
    }
  }

  /**
   * Smarty variables describing the task and the current execution.
   *
   * Without these, a notification cannot name the task it belongs to, and the
   * log is only reachable through the optional attachment.
   *
   * @return array<string, mixed>
   */
  protected function getTaskTemplateParams(): array {
    $taskParams = [
      'sqltask' => [
        'id'          => $this->task->id,
        'name'        => $this->task->name,
        'description' => $this->task->description,
      ],
    ];

    $execution = $this->context['execution'] ?? NULL;

    if ($execution instanceof CRM_Sqltasks_BAO_SqltasksExecution) {
      $result = $execution->result();

      $taskParams['sqltask_execution'] = [
        'id'          => $execution->id,
        'error_count' => $result['error_count'],
        'log'         => $result['logs'],
        'runtime'     => $result['runtime'],
        'status'      => $result['status'],
      ];
    }

    return $taskParams;
  }

}
