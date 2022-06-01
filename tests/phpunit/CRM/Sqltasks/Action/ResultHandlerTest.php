<?php

/**
 * Test ResultHandler Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_ResultHandlerTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function testSuccessHandler() {
    $mailUtils = new CiviMailUtils($this, TRUE);
    $data = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_successhandler;
                        CREATE TABLE tmp_test_action_successhandler AS " . self::TEST_CONTACT_SQL,
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
          'email_template' => '1'
        ],
      ],
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains("Action 'Create Activity' executed in", 'Create Activity action should have succeeded');
    $mailUtils->checkMailLog([
      'successhandler@example.com',
    ]);
    $mailUtils->stop();
  }

  public function testErrorHandler() {
    $mailUtils = new CiviMailUtils($this, TRUE);
    // contains invalid activity_activity_type_id, should cause error
    $data = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_errorhandler;
                        CREATE TABLE tmp_test_action_errorhandler AS SELECT contact_id FROM civicrm_email WHERE email='john.doe@example.com';",
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
          'email_template' => '1'
        ],
      ],
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains("Error in action 'Create Activity'", 'Create Activity action should have failed');
    $mailUtils->checkMailLog([
      'errorhandler@example.com',
    ]);
    $mailUtils->stop();
  }

}
