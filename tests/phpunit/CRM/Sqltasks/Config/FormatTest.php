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
        "scheduled_minute": "0"
    }
  }';

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

}
