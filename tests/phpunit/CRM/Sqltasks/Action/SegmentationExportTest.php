<?php

/**
 * Test SegmentationExport Action, requires de.systopia.segmentation
 *
 * @group headless
 */
class CRM_Sqltasks_Action_SegmentationExportTest extends CRM_Sqltasks_Action_AbstractActionTest {

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

  public function testSegmentationExport() {
    if (!$this->segmentationPresent) {
      $this->markTestSkipped(
        'The de.systopia.segmentation extension is not available.'
      );
    }
    $segmentId = $this->callApiSuccess('Segmentation', 'getsegmentid', [
      'name' => 'testSegmentationExport',
    ])['id'];
    $campaignId = $this->callApiSuccess('Campaign', 'create', array(
      'sequential' => 1,
      'name'       => 'testSegmentationExport',
      'title'      => 'testSegmentationExport',
    ))['id'];
    $tmp = tempnam(sys_get_temp_dir(), 'seg');
    $data = [
      'main_sql'                                => "DROP TABLE IF EXISTS tmp_test_action_segmentationexport;
                                                    CREATE TABLE tmp_test_action_segmentationexport AS " . self::TEST_CONTACT_SQL,
      'post_sql'                                => 'DROP TABLE IF EXISTS tmp_test_action_segmentationexport;',
      'segmentation_assign_enabled'             => '1',
      'segmentation_assign_table'               => 'tmp_test_action_segmentationexport',
      'segmentation_assign_campaign_id'         => $campaignId,
      'segmentation_assign_segment_name'        => 'testSegmentationExport',
      'segmentation_assign_start'               => 'leave',
      'segmentation_assign_segment_order'       => '',
      'segmentation_assign_segment_order_table' => '',
      'segmentation_export_enabled'             => '1',
      'segmentation_export_campaign_id'         => $campaignId,
      'segmentation_export_segments'            => [$segmentId],
      'segmentation_export_exporter'            => [2],
      'segmentation_export_date_from'           => '',
      'segmentation_export_date_to'             => '',
      'segmentation_export_filename'            => basename($tmp),
      'segmentation_export_path'                => dirname($tmp),
      'segmentation_export_email'               => '',
      'segmentation_export_email_template'      => '1',
      'segmentation_export_upload'              => '',
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains("Exporter 'Selektion (Excel)' to file", 'Should have exported file');
    $this->assertLogContains('Zipped file into', 'Should have zipped file');
    $this->assertLogContains("Action 'Segmentation Export' executed in", 'Segmentation Export action should have succeeded');
    $zip = new ZipArchive();
    $zip->open($tmp);
    $this->assertContains(
      'contact_id;titel;anrede;vorname;nachname;geburtsdatum;strasse;plz;ort;land;zielgruppe ID;zielgruppe;telefon;mobilnr;email;paket;textbaustein',
      $zip->getFromIndex(0)
    );
    $this->assertContains(
      "{$this->contactId};;An;John;Doe;;;;;;{$segmentId};testSegmentationExport;;;john.doe@example.com;;",
      $zip->getFromIndex(0)
    );
  }

}
