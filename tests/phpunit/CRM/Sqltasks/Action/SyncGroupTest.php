<?php

/**
 * Test SyncGroup Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_SyncGroupTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function testSyncGroup() {
    $groupId = $this->callApiSuccess('Group', 'create', array(
      'sequential' => 1,
      'name' => 'testSyncGroup',
      'title' => 'testSyncGroup',
    ))['id'];
    $data = [
      'main_sql'            => "DROP TABLE IF EXISTS tmp_test_action_syncgroup;
                                CREATE TABLE tmp_test_action_syncgroup AS " . self::TEST_CONTACT_SQL,
      'post_sql'            => 'DROP TABLE IF EXISTS tmp_test_action_syncgroup;',
      'group_enabled'       => '1',
      'group_contact_table' => 'tmp_test_action_syncgroup',
      'group_group_id'      => $groupId,
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains("Action 'Synchronise Group' executed in", 'Synchronize Group action should have succeeded');
    $groupContactCount = $this->callApiSuccess('GroupContact', 'getcount', [
      'contact_id' => $this->contactId,
      'group_id'   => $groupId,
      'status'     => 'Added',
    ]);
    $this->assertEquals(1, $groupContactCount, 'Contact should have been added to group');
    $totalGroupContactCount = $this->callApiSuccess('GroupContact', 'getcount', [
      'group_id' => $groupId,
      'status'   => 'Added',
    ]);
    $this->assertEquals(1, $totalGroupContactCount, 'Should have added one contact to group');
    // there's no API for civicrm_subscription_history, using SQL
    $this->assertEquals(
      1,
      CRM_Core_DAO::singleValueQuery(
        "SELECT COUNT(*) FROM civicrm_subscription_history WHERE group_id = %0 AND status = 'Added' AND contact_id = %1",
        [
          [$groupId, 'Integer'],
          [$this->contactId, 'Integer'],
        ]
      ),
      'Should have created subscription history'
    );
  }

}
