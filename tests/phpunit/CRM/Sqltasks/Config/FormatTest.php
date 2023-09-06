<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;

/**
 * Test configuration format utility class
 *
 * @group headless
 */
class CRM_Sqltasks_Config_FormatTest extends CRM_Sqltasks_AbstractTaskTest {

  const SAMPLE_V1 = '{
    "description": "Sample description",
    "category": "Sample",
    "scheduled": "daily",
    "last_runtime": "1234",
    "parallel_exec": null,
    "main_sql": "sample main script",
    "post_sql": "sample post script",
    "config": {
        "segmentation_assign_table": "",
        "segmentation_assign_campaign_id": "",
        "segmentation_assign_segment_name": "",
        "segmentation_assign_start": "leave",
        "segmentation_assign_segment_order": "",
        "segmentation_assign_segment_order_table": "",
        "scheduled_month": "",
        "scheduled_weekday": "",
        "scheduled_day": "",
        "scheduled_hour": "0",
        "scheduled_minute": "0",
        "tag_enabled":"1",
        "tag_contact_table":"temp_foo",
        "tag_tag_id":"123"
    }
  }';

  const SAMPLE_LEGACY = '/* ##### SQLTASK VERSION 0.9 ###########
{
    "description": "Sample description",
    "category": "Sample",
    "scheduled": "daily",
    "parallel_exec": null,
    "run_permissions": null,
    "config": {
        "scheduled_month": "1",
        "scheduled_weekday": "1",
        "scheduled_day": "1",
        "scheduled_hour": "0",
        "scheduled_minute": "0",
        "tag_enabled": "1",
        "tag_contact_table": "temp_foo",
        "tag_tag_id": "123",
        "tag_entity_table": "civicrm_contact"
    }
}
*/ ############ MAIN SQL ###############
sample main script
-- ############ POST SQL ###############
sample post script';

  const SAMPLE_V2_APPENDED_SCRIPT = '{
    "description": "Sample description",
    "category": "Sample",
    "scheduled": "daily",
    "parallel_exec": 0,
    "run_permissions": "",
    "main_sql": null,
    "post_sql": null,
    "input_required": 0,
    "abort_on_error": "1",
    "config": {
        "version": "2",
        "actions": [
            {
                "type": "CRM_Sqltasks_Action_RunSQL",
                "script": "sample main script",
                "enabled": "1"
            },
            {
                "type": "CRM_Sqltasks_Action_SyncTag",
                "entity_table": "civicrm_contact",
                "enabled": "1",
                "contact_table": "temp_foo",
                "tag_id": "123"
            }
        ],
        "scheduled_hour": "0",
        "scheduled_minute": "0"
    }
}
##### EDITS BELOW THIS LINE WILL BE IGNORED #####
##### SQL Script 1 #####
sample main script
##### Cleanup SQL Script 1 #####
sample post script
';

  public function testVersion1IsNotLatest() {
    $this->assertFalse(CRM_Sqltasks_Config_Format::isLatest(json_decode(self::SAMPLE_V1, TRUE)));
  }

  public function testVersion2IsLatest() {
    $config = json_decode('{"version":2,"actions":[]}', TRUE);
    $this->assertTrue(CRM_Sqltasks_Config_Format::isLatest($config));
  }

  public function testVersion1ToLatest() {
    $this->assertTrue(
      CRM_Sqltasks_Config_Format::isLatest(
        CRM_Sqltasks_Config_Format::toLatest(
          json_decode(self::SAMPLE_V1, TRUE),
          TRUE
        )['config']
      )
    );
  }

  public function testLegacyToLatest() {
    $this->assertTrue(
      CRM_Sqltasks_Config_Format::isLatest(
        CRM_Sqltasks_Config_Format::toLatest(
          self::SAMPLE_LEGACY
        )['config']
      )
    );
  }

  public function testV2AppendedToLatest() {
    $this->assertTrue(
      CRM_Sqltasks_Config_Format::isLatest(
        CRM_Sqltasks_Config_Format::toLatest(
          self::SAMPLE_V2_APPENDED_SCRIPT
        )['config']
      )
    );
  }

  /**
   * Test that task actions are added
   *
   * @throws \Exception
   */
  public function testTaskActionsAreAdded() {
    $samplesToTest = [
      json_decode(self::SAMPLE_V1, TRUE),
      self::SAMPLE_V1,
      self::SAMPLE_LEGACY,
      self::SAMPLE_V2_APPENDED_SCRIPT,
    ];
    foreach ($samplesToTest as $sample) {
      $config = CRM_Sqltasks_Config_Format::toLatest(
        $sample
      )['config'];
      $entity_table = $tag_id = $contact_table = $enabled = NULL;
      foreach ($config['actions'] as $action) {
        if ($action['type'] == 'CRM_Sqltasks_Action_SyncTag') {
          $entity_table = $action['entity_table'];
          $tag_id = $action['tag_id'];
          $contact_table = $action['contact_table'];
          $enabled = $action['enabled'];
        }
        if ($action['type'] == 'CRM_Sqltasks_Action_RunSQL') {
          $script = $action['script'];
        }
      }
      $this->assertEquals('civicrm_contact', $entity_table);
      $this->assertEquals('123', $tag_id);
      $this->assertEquals('temp_foo', $contact_table);
      $this->assertEquals('1', $enabled);
      $this->assertEquals('sample main script', $script);

    }
  }

}
