<?php

use Civi\Api4\EntityTag;

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

    // create another contact and assign it to the tag
    $secondContactId = $this->callApiSuccess('Contact', 'create', [
      'first_name'   => 'Jane',
      'last_name'    => 'Doe',
      'contact_type' => 'Individual',
      'email'        => 'jane.doe@example.com',
    ])['id'];
    EntityTag::create(FALSE)
      ->addValue('entity_table', 'civicrm_contact')
      ->addValue('entity_id', $secondContactId)
      ->addValue('tag_id', $tagId)
      ->execute();
    $entityTagCount = $this->callApiSuccess('EntityTag', 'getcount', [
      'entity_table' => 'civicrm_contact',
      'entity_id'    => $secondContactId,
      'tag_id'       => $tagId,
    ]);
    $this->assertEquals(1, $entityTagCount, 'Second contact should have been tagged');

    // re-run the task and ensure the manually-added contact was removed
    $this->createAndExecuteTask($data);
    $this->assertLogContains("Action 'Synchronise Tag' executed in", 'Synchronize Tag action should have succeeded');

    $entityTagCount = $this->callApiSuccess('EntityTag', 'getcount', [
      'entity_table' => 'civicrm_contact',
      'entity_id'    => $secondContactId,
      'tag_id'       => $tagId,
    ]);
    $this->assertEquals(0, $entityTagCount, 'Second contact should no longer be tagged');
  }

}
