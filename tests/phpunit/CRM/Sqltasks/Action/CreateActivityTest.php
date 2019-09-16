<?php

/**
 * Test CreateActivity Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_CreateActivityTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function testCreateActivity() {
    $data = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_createactivity;
                        CREATE TABLE tmp_test_action_createactivity AS " . self::TEST_CONTACT_SQL,
        ],
        [
          'type'               => 'CRM_Sqltasks_Action_CreateActivity',
          'enabled'            => TRUE,
          'contact_table'      => 'tmp_test_action_createactivity',
          'activity_type_id'   => '3',
          'status_id'          => '2',
          'subject'            => 'testCreateActivity',
          'details'            => '',
          'activity_date_time' => '',
          'campaign_id'        => '0',
          'source_contact_id'  => '1',
          'assigned_to'        => '',
        ],
        [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_createactivity',
        ],
      ],
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
