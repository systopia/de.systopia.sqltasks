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
      'main_sql'                    => "DROP TABLE IF EXISTS tmp_test_action_successhandler;
                                        CREATE TABLE tmp_test_action_successhandler AS " . self::TEST_CONTACT_SQL,
      'post_sql'                    => 'DROP TABLE IF EXISTS tmp_test_action_successhandler;',
      'activity_enabled'            => '1',
      'activity_contact_table'      => 'tmp_test_action_successhandler',
      'activity_activity_type_id'   => '3',
      'activity_status_id'          => '2',
      'activity_subject'            => 'testSuccessHandler',
      'activity_details'            => '',
      'activity_activity_date_time' => '',
      'activity_campaign_id'        => '0',
      'activity_source_contact_id'  => '1',
      'activity_assigned_to'        => '',
      'success_enabled'             => '1',
      'success_table'               => '',
      'success_email'               => 'successhandler@example.com',
      'success_email_template'      => '1',
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
      'main_sql'                    => "DROP TABLE IF EXISTS tmp_test_action_errorhandler;
                                        CREATE TABLE tmp_test_action_errorhandler AS SELECT contact_id FROM civicrm_email WHERE email='john.doe@example.com';",
      'post_sql'                    => 'DROP TABLE IF EXISTS tmp_test_action_errorhandler;',
      'activity_enabled'            => '1',
      'activity_contact_table'      => 'tmp_test_action_errorhandler',
      'activity_activity_type_id'   => '999999',
      'activity_status_id'          => '2',
      'activity_subject'            => 'testErrorHandler',
      'activity_details'            => '',
      'activity_activity_date_time' => '',
      'activity_campaign_id'        => '0',
      'activity_source_contact_id'  => '1',
      'activity_assigned_to'        => '',
      'error_enabled'               => '1',
      'error_table'                 => '',
      'error_email'                 => 'errorhandler@example.com',
      'error_email_template'        => '1',
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains("Error in action 'Create Activity'", 'Create Activity action should have failed');
    $mailUtils->checkMailLog([
      'errorhandler@example.com',
    ]);
    $mailUtils->stop();
  }

}
