<?php

/**
 * Test SyncTag Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_SyncTagTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function testSyncTag() {
    $tagId = $this->callApiSuccess('Tag', 'create', [
      'name'     => 'test',
      'used_for' => 'Contacts',
    ])['id'];
    $data = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_synctag;
                        CREATE TABLE tmp_test_action_synctag AS " . self::TEST_CONTACT_SQL,
        ],
        [
          'type'          => 'CRM_Sqltasks_Action_SyncTag',
          'enabled'       => TRUE,
          'contact_table' => 'tmp_test_action_synctag',
          'tag_id'        => $tagId,
          'entity_table'  => 'civicrm_contact',
        ],
        [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_synctag;',
        ],
      ]
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains("Action 'Synchronise Tag' executed in", 'Synchronize Tag action should have succeeded');
    $entityTagCount = $this->callApiSuccess('EntityTag', 'getcount', [
      'entity_table' => 'civicrm_contact',
      'entity_id'    => $this->contactId,
      'tag_id'       => $tagId,
    ]);
    $this->assertEquals(1, $entityTagCount, 'Contact should have been tagged');
    $totalEntityTagCount = $this->callApiSuccess('EntityTag', 'getcount', [
      'entity_table' => 'civicrm_contact',
      'tag_id'       => $tagId,
    ]);
    $this->assertEquals(1, $totalEntityTagCount, 'Should have tagged one contact');
  }

}
