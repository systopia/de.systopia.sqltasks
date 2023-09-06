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
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_segmentationexport;
                        CREATE TABLE tmp_test_action_segmentationexport AS " . self::TEST_CONTACT_SQL,
        ],
        [
          'type'                => 'CRM_Sqltasks_Action_SegmentationAssign',
          'enabled'             => TRUE,
          'table'               => 'tmp_test_action_segmentationexport',
          'campaign_id'         => $campaignId,
          'segment_name'        => 'testSegmentationExport',
          'start'               => 'leave',
          'segment_order'       => '',
          'segment_order_table' => '',
        ],
        [
          'type'           => 'CRM_Sqltasks_Action_SegmentationExport',
          'enabled'        => TRUE,
          'campaign_id'    => $campaignId,
          'segments'       => [$segmentId],
          'exporter'       => [2],
          'date_from'      => '',
          'date_to'        => '',
          'filename'       => basename($tmp),
          'path'           => dirname($tmp),
          'email'          => '',
          'email_template' => '1',
          'upload'         => '',
        ],
        [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_segmentationexport;',
        ],
      ],
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
