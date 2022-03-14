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

  public function testStoreActivityID_MassActivity() {
    $tmpContactTable = 'tmp_test_action_createactivity';
    $contactIDs = [];

    for ($i = 0; $i < 3; $i++) {
      $contactIDs[] = self::createRandomTestContact();
    }

    foreach ([TRUE, FALSE] as $use_api) {
      $uniqueID = bin2hex(random_bytes(8));
      $activitySubject = "testStoreActivityID: $uniqueID";

      $config = [
        'version' => CRM_Sqltasks_Config_Format::CURRENT,
        'actions' => [
          self::getCreateTempContactTableAction($tmpContactTable, $contactIDs),
          [
            'type'               => 'CRM_Sqltasks_Action_CreateActivity',
            'activity_type_id'   => '3',
            'contact_table'      => $tmpContactTable,
            'enabled'            => TRUE,
            'individual'         => FALSE,
            'source_contact_id'  => $this->contactId,
            'status_id'          => '2',
            'store_activity_ids' => TRUE,
            'subject'            => $activitySubject,
            'use_api'            => $use_api,
          ],
        ],
      ];

      $this->createAndExecuteTask($config);

      $activityResult = $this->callApiSuccess('Activity', 'getsingle', [
        'subject' => $activitySubject,
      ]);

      $queryResult = CRM_Core_DAO::executeQuery("SELECT `activity_id`, `contact_id` FROM `$tmpContactTable`");

      while ($queryResult->fetch()) {
        $this->assertObjectHasAttribute(
          'activity_id',
          $queryResult,
          'Temporary table should have a activity_id column'
        );

        $this->assertEquals(
          $activityResult['id'],
          $queryResult->activity_id,
          'Row should contain the ID of the created activity'
        );
      }
    }

    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `$tmpContactTable`");
  }

  public function testStoreActivityID_IndividualActivities() {
    $tmpContactTable = 'tmp_test_action_createactivity';
    $contactIDs = [];

    for ($i = 0; $i < 3; $i++) {
      $contactIDs[] = self::createRandomTestContact();
    }

    foreach ([TRUE, FALSE] as $use_api) {
      $config = [
        'version' => CRM_Sqltasks_Config_Format::CURRENT,
        'actions' => [
          self::getCreateTempContactTableAction($tmpContactTable, $contactIDs),
          [
            'type'               => 'CRM_Sqltasks_Action_CreateActivity',
            'activity_type_id'   => '3',
            'contact_table'      => $tmpContactTable,
            'enabled'            => TRUE,
            'individual'         => TRUE,
            'source_contact_id'  => $this->contactId,
            'status_id'          => '2',
            'store_activity_ids' => TRUE,
            'subject'            => 'testStoreActivityID',
            'use_api'            => $use_api,
          ],
        ],
      ];

      $this->createAndExecuteTask($config);

      $queryResult = CRM_Core_DAO::executeQuery("SELECT `activity_id`, `contact_id` FROM `$tmpContactTable`");

      while ($queryResult->fetch()) {
        $this->assertObjectHasAttribute(
          'activity_id',
          $queryResult,
          'Temporary table should have a activity_id column'
        );

        $this->assertNotNull(
          $queryResult->activity_id,
          'Field activity_id should not be null'
        );

        $activityContactCount = $this->callAPISuccessGetCount('ActivityContact', [
          'activity_id'    => $queryResult->activity_id,
          'contact_id'     => $queryResult->contact_id,
          'record_type_id' => "Activity Targets",
        ]);

        $this->assertEquals(
          1,
          $activityContactCount,
          'There should be exactly 1 activity target contact'
        );
      }
    }

    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `$tmpContactTable`");
  }

}
