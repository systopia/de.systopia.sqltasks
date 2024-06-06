<?php

/**
 * Test CreateActivity Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_CreateActivityTest extends CRM_Sqltasks_Action_AbstractActionTest {
  public $campaignID;

  public function setUp() : void {
    parent::setUp();

    $campaignResult = $this->callAPISuccess('Campaign', 'create', [
      'title' => 'Test Campaign',
    ]);

    $this->campaignID = (int) $campaignResult['id'];

    $this->callAPISuccess('OptionValue', 'create', [
      'label'           => 'Exclusion Record',
      'name'            => 'Exclusion Record',
      'option_group_id' => 'activity_type',
    ]);

    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `civicrm_segmentation_exclude`");

    CRM_Core_DAO::executeQuery(
      "CREATE TABLE `civicrm_segmentation_exclude` (campaign_id INT, contact_id INT)"
    );
  }

  public function tearDown() : void {
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `civicrm_segmentation_exclude`");
    parent::tearDown();
  }

  public function testCreateActivity() {
    $config = [
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

    $this->createAndExecuteTask([ 'config' => $config ]);

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
    $tableRows = [];

    for ($i = 0; $i < 3; $i++) {
      $contactID = self::createRandomTestContact();

      $tableRows[] = [
        'contact_id' => $contactID,
        'exclude'    => $i % 2,
      ];

      CRM_Core_DAO::executeQuery(
        "INSERT INTO `civicrm_segmentation_exclude` (campaign_id, contact_id) VALUES (%1, %2)",
        [
          1 => [$this->campaignID, 'Integer'],
          2 => [$contactID, 'Integer'],
        ]
      );
    }

    foreach ([TRUE, FALSE] as $use_api) {
      $uniqueID = bin2hex(random_bytes(8));
      $activitySubject = "testStoreActivityID: $uniqueID";

      $config = [
        'version' => CRM_Sqltasks_Config_Format::CURRENT,
        'actions' => [
          self::getCreateTempContactTableAction($tmpContactTable, $tableRows),
          [
            'type'               => 'CRM_Sqltasks_Action_CreateActivity',
            'activity_type_id'   => '3',
            'campaign_id'        => $this->campaignID,
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

      $this->createAndExecuteTask([ 'config' => $config ]);

      $activityResult = $this->callApiSuccess('Activity', 'getsingle', [
        'subject' => $activitySubject,
      ]);

      $queryResult = CRM_Core_DAO::executeQuery(
        "SELECT `sqltask_activity_id`, `contact_id`, `exclude` FROM `$tmpContactTable`"
      );

      while ($queryResult->fetch()) {
        $this->assertObjectHasAttribute(
          'sqltask_activity_id',
          $queryResult,
          'Temporary table should have a sqltask_activity_id column'
        );

        $this->assertNotNull(
          $queryResult->sqltask_activity_id,
          'Field sqltask_activity_id should not be null'
        );

        if ((int) $queryResult->exclude){
          $exclActivity = $this->callAPISuccess('Activity', 'getsingle', [
            'id' => $queryResult->sqltask_activity_id,
          ]);

          $this->assertEquals(
            CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Exclusion Record'),
            $exclActivity['activity_type_id'],
            'Associated activity should be of type "Exclusion Record"'
          );

          continue;
        }

        $this->assertEquals(
          $activityResult['id'],
          $queryResult->sqltask_activity_id,
          'Row should contain the ID of the created activity'
        );
      }
    }

    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `$tmpContactTable`");
  }

  public function testStoreActivityID_IndividualActivities() {
    $tmpContactTable = 'tmp_test_action_createactivity';
    $tableRows = [];

    for ($i = 0; $i < 3; $i++) {
      $contactID = self::createRandomTestContact();

      $tableRows[] = [
        'contact_id' => $contactID,
        'exclude'    => $i % 2,
      ];

      CRM_Core_DAO::executeQuery(
        "INSERT INTO `civicrm_segmentation_exclude` (campaign_id, contact_id) VALUES (%1, %2)",
        [
          1 => [$this->campaignID, 'Integer'],
          2 => [$contactID, 'Integer'],
        ]
      );
    }

    foreach ([TRUE, FALSE] as $use_api) {
      $config = [
        'version' => CRM_Sqltasks_Config_Format::CURRENT,
        'actions' => [
          self::getCreateTempContactTableAction($tmpContactTable, $tableRows),
          [
            'type'               => 'CRM_Sqltasks_Action_CreateActivity',
            'activity_type_id'   => '3',
            'campaign_id'        => $this->campaignID,
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

      $this->createAndExecuteTask([ 'config' => $config ]);

      $queryResult = CRM_Core_DAO::executeQuery(
        "SELECT `sqltask_activity_id`, `contact_id`, `exclude` FROM `$tmpContactTable`"
      );

      while ($queryResult->fetch()) {
        $this->assertObjectHasAttribute(
          'sqltask_activity_id',
          $queryResult,
          'Temporary table should have a sqltask_activity_id column'
        );

        $this->assertNotNull(
          $queryResult->sqltask_activity_id,
          'Field sqltask_activity_id should not be null'
        );

        if ((int) $queryResult->exclude) {
          $exclActivity = $this->callAPISuccess('Activity', 'getsingle', [
            'id' => $queryResult->sqltask_activity_id,
          ]);

          $this->assertEquals(
            CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Exclusion Record'),
            $exclActivity['activity_type_id'],
            'Associated activity should be of type "Exclusion Record"'
          );

          continue;
        }

        $activityContactCount = $this->callAPISuccessGetCount('ActivityContact', [
          'activity_id'    => $queryResult->sqltask_activity_id,
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
