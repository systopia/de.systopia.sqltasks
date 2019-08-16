<?php

/**
 * Test SegmentationAssign Action, requires de.systopia.segmentation
 *
 * @group headless
 */
class CRM_Sqltasks_Action_SegmentationAssignTest extends CRM_Sqltasks_Action_AbstractActionTest {

  private $segmentationPresent;

  public function setUpHeadless() {
    $test = \Civi\Test::headless()
      ->uninstallMe(__DIR__)
      ->installMe(__DIR__);
    $this->segmentationPresent = $this->callApiSuccess('Extension', 'getcount', [
      'full_name' => 'de.systopia.segmentation',
    ]);
    if ($this->segmentationPresent) {
      $test->uninstall('de.systopia.segmentation');
      $test->install('de.systopia.segmentation');
    }
    return $test->apply(TRUE);
  }

  public function testSegmentationAssign() {
    if (!$this->segmentationPresent) {
      $this->markTestSkipped(
        'The de.systopia.segmentation extension is not available.'
      );
    }
    $campaignId = $this->callApiSuccess('Campaign', 'create', array(
      'sequential' => 1,
      'name'       => 'testCampaign',
      'title'      => 'testCampaign',
    ))['id'];
    $data = [
      'main_sql'                                => "DROP TABLE IF EXISTS tmp_test_action_segmentationassign;
                                                    CREATE TABLE tmp_test_action_segmentationassign AS " . self::TEST_CONTACT_SQL,
      'post_sql'                                => 'DROP TABLE IF EXISTS tmp_test_action_segmentationassign;',
      'segmentation_assign_enabled'             => '1',
      'segmentation_assign_table'               => 'tmp_test_action_segmentationassign',
      'segmentation_assign_campaign_id'         => $campaignId,
      'segmentation_assign_segment_name'        => 'testSegmentationAssign',
      'segmentation_assign_start'               => 'leave',
      'segmentation_assign_segment_order'       => '',
      'segmentation_assign_segment_order_table' => '',
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains('Resolved 1 segment(s).', 'Should have resolved one segment');
    $this->assertLogContains("Assigned 1 new contacts to segment 'testSegmentationAssign'.", 'Should have assigned one contact to segment "testSegmentationAssign"');
    $this->assertLogContains("Action 'Assign to Campaign (Segmentation)' executed in", 'Assign to Campaign action should have succeeded');
    $this->assertEquals(
      1,
      CRM_Core_DAO::singleValueQuery(
        "SELECT COUNT(*) FROM civicrm_segmentation WHERE campaign_id = %0 AND entity_id = %1",
        [
          [$campaignId, 'Integer'],
          [$this->contactId, 'Integer'],
        ]
      ),
      'Should have added contact to segment'
    );
  }

}
