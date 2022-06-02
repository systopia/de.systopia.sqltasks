<?php

/**
 * Trait for Action classes that send out email
 */
trait CRM_Sqltasks_Action_EmailActionTrait {

  public function sendEmailMessage(array $params) {
    [$domainEmailName, $domainEmailAddress] = CRM_Core_BAO_Domain::getNameAndEmail();

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

    foreach ($to_email as $email) {
      $email = trim($email);
      civicrm_api3('MessageTemplate', 'send', array_merge(
        $templateParams,
        ['to_email' => $email],
      ));
      $this->log("Sent {$this->id} message to '{$email}'");
    }
  }

}
