<?php

/**
 * Test CreateActivity Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_CreateActivityTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function testCreateActivity() {
    $data = [
      'main_sql'                    => "DROP TABLE IF EXISTS tmp_test_action_createactivity;
                                        CREATE TABLE tmp_test_action_createactivity AS " . self::TEST_CONTACT_SQL,
      'post_sql'                    => 'DROP TABLE IF EXISTS tmp_test_action_createactivity',
      'activity_enabled'            => '1',
      'activity_contact_table'      => 'tmp_test_action_createactivity',
      'activity_activity_type_id'   => '3',
      'activity_status_id'          => '2',
      'activity_subject'            => 'testCreateActivity',
      'activity_details'            => '',
      'activity_activity_date_time' => '',
      'activity_campaign_id'        => '0',
      'activity_source_contact_id'  => '1',
      'activity_assigned_to'        => '',
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains("Action 'Create Activity' executed in", 'Create Activity action should have succeeded');
    $activityCount = $this->callApiSuccess('Phone', 'getcount', [
      'target_contact_id'          => $this->contactId,
      'subject'                    => 'testCreateActivity',
      'activity_status_id'         => '2',
      'activity_source_contact_id' => '1',
      'activity_activity_type_id'  => '3',
    ]);
    $this->assertEquals(1, $activityCount, 'Activity should have been added');
  }

}
