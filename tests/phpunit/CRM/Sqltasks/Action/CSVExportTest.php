<?php

/**
 * Test CSVExport Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_CSVExportTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function testFileExport() {
    $tmp = tempnam(sys_get_temp_dir(), 'csv');
    $data = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_csvexport;
                        CREATE TABLE tmp_test_action_csvexport AS SELECT email, is_primary FROM civicrm_email WHERE email='john.doe@example.com';",
        ],
        [
          'type'           => 'CRM_Sqltasks_Action_CSVExport',
          'enabled'        => TRUE,
          'table'          => 'tmp_test_action_csvexport',
          'encoding'       => 'UTF-8',
          'delimiter'      => ';',
          'headers'        => "contact_email=email\r\nis_primary=is_primary",
          'filename'       => basename($tmp),
          'path'           => dirname($tmp),
          'email'          => '',
          'email_template' => '1',
          'upload'         => '',
        ],
        [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_csvexport;',
        ],
      ],
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains('Written 1 records to', 'Records should have been written to CSV');
    $this->assertLogContains("Action 'CSV Export' executed in", 'CSV Export action should have succeeded');
    $this->assertFileEquals(__DIR__ . '/../../../../fixtures/csvexport.csv', $tmp);
  }

}
