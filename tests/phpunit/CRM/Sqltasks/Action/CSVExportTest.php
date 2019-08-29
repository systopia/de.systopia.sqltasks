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
      'main_sql'           => "DROP TABLE IF EXISTS tmp_test_action_csvexport;
                               CREATE TABLE tmp_test_action_csvexport AS SELECT email, is_primary FROM civicrm_email WHERE email='john.doe@example.com';",
      'post_sql'           => 'DROP TABLE IF EXISTS tmp_test_action_csvexport;',
      'csv_enabled'        => '1',
      'csv_table'          => 'tmp_test_action_csvexport',
      'csv_encoding'       => 'UTF-8',
      'csv_delimiter'      => ';',
      'csv_headers'        => "contact_email=email\r\nis_primary=is_primary",
      'csv_filename'       => basename($tmp),
      'csv_path'           => dirname($tmp),
      'csv_email'          => '',
      'csv_email_template' => '1',
      'csv_upload'         => '',
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains('Written 1 records to', 'Records should have been written to CSV');
    $this->assertLogContains("Action 'CSV Export' executed in", 'CSV Export action should have succeeded');
    $this->assertFileEquals(__DIR__ . '/../../../../fixtures/csvexport.csv', $tmp);
  }

}
