<?php

declare(strict_types = 1);

/**
 * Test ResultHandler Action
 *
 * @group headless
 *
 * @covers \CRM_Sqltasks_Action_ResultHandler
 */
class CRM_Sqltasks_Action_ResultHandlerTest extends CRM_Sqltasks_Action_AbstractActionTest {

  /**
   * @var array<int, array<string, mixed>>
   */
  protected $sentMails = [];

  public function setUp() : void {
    $this->sentMails = [];
    parent::setUp();
  }

  /**
   * @param array<string, mixed> $params
   * @param string $context
   */
  public function hook_civicrm_alterMailParams(&$params, $context): void {
    $this->sentMails[] = $params;
  }

  protected function assertMailSentTo(string $email, string $message = ''): void {
    $recipients = array_column($this->sentMails, 'toEmail');
    self::assertContains($email, $recipients, $message);
  }

  public function testSuccessHandler() {
    $config = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_successhandler;
                        CREATE TABLE tmp_test_action_successhandler AS ' . self::TEST_CONTACT_SQL,
        ],
        [
          'type'               => 'CRM_Sqltasks_Action_CreateActivity',
          'enabled'            => TRUE,
          'contact_table'      => 'tmp_test_action_successhandler',
          'activity_type_id'   => '3',
          'status_id'          => '2',
          'subject'            => 'testSuccessHandler',
          'details'            => '',
          'activity_date_time' => '',
          'campaign_id'        => '0',
          'source_contact_id'  => '1',
          'assigned_to'        => '',
        ],
        [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_successhandler;',
        ],
        [
          'type'           => 'CRM_Sqltasks_Action_SuccessHandler',
          'enabled'        => TRUE,
          'email'          => 'successhandler@example.com',
          'email_template' => '1',
        ],
      ],
    ];

    $this->createAndExecuteTask(['config' => $config]);

    $this->assertLogContains("Action 'Create Activity' executed in", 'Create Activity action should have succeeded');
    $this->assertMailSentTo(
      'successhandler@example.com',
      'Success handler should have sent a mail'
    );
  }

  public function testErrorHandler() {
    // Contains invalid activity_activity_type_id, should cause error
    $config = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_errorhandler;
                        CREATE TABLE tmp_test_action_errorhandler AS
                          SELECT contact_id FROM civicrm_email WHERE email='john.doe@example.com';",
        ],
        [
          'type'               => 'CRM_Sqltasks_Action_CreateActivity',
          'enabled'            => TRUE,
          'contact_table'      => 'tmp_test_action_errorhandler',
          'activity_type_id'   => '999999',
          'status_id'          => '2',
          'subject'            => 'testErrorHandler',
          'details'            => '',
          'activity_date_time' => '',
          'campaign_id'        => '0',
          'source_contact_id'  => '1',
          'assigned_to'        => '',
        ],
        [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_successhandler;',
        ],
        [
          'type'           => 'CRM_Sqltasks_Action_ErrorHandler',
          'enabled'        => TRUE,
          'email'          => 'errorhandler@example.com',
          'email_template' => '1',
        ],
      ],
    ];

    $this->createAndExecuteTask(['config' => $config]);

    $this->assertLogContains("Error in action 'Create Activity'", 'Create Activity action should have failed');
    $this->assertMailSentTo(
      'errorhandler@example.com',
      'Error handler should have sent a mail'
    );
  }

  /**
   * A task that succeeds does not run its error handler. The log should say so
   * in terms of the error handler, not of a success handler the task may not
   * even have.
   */
  public function testSkippedErrorHandlerIsNamedInTheLog() {
    $config = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_skippedhandler;
                        CREATE TABLE tmp_test_action_skippedhandler AS ' . self::TEST_CONTACT_SQL,
        ],
        [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_skippedhandler;',
        ],
        [
          'type'           => 'CRM_Sqltasks_Action_ErrorHandler',
          'enabled'        => TRUE,
          'email'          => 'errorhandler@example.com',
          'email_template' => '1',
        ],
      ],
    ];

    $this->createAndExecuteTask(['config' => $config]);

    $this->assertLogContains(
      'Skipping Error Handler, no errors were recorded',
      'The skipped handler should be named, with the reason it was skipped'
    );
    $this->assertStringNotContainsString(
      'Success Handler',
      implode("\n", $this->log),
      'This task has no success handler, so the log should not mention one'
    );
  }

  /**
   * The success handler keeps its own message: it is skipped when none of the
   * actions reported doing anything.
   */
  public function testSkippedSuccessHandlerKeepsItsMessage() {
    $config = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => 'SELECT 1;',
        ],
        [
          'type'           => 'CRM_Sqltasks_Action_SuccessHandler',
          'enabled'        => TRUE,
          'email'          => 'successhandler@example.com',
          'email_template' => '1',
        ],
      ],
    ];

    $this->createAndExecuteTask(['config' => $config]);

    $this->assertLogContains(
      "Skipping Success Handler, actions didn't do anything",
      'The success handler message should be unchanged'
    );
  }

}
