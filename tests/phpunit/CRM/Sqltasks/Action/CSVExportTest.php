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

  public function testFieldEnclosureTypes () {
    $enclosureModes = ["none", "partial", "full"];

    foreach ($enclosureModes as $mode) {
      $outputFilename = tempnam(sys_get_temp_dir(), "csv-$mode-");
      $tmpTable = "tmp_test_action_csvexport";

      $taskConfig = [
        "version" => CRM_Sqltasks_Config_Format::CURRENT,
        "actions" => [
          [
            "type"    => "CRM_Sqltasks_Action_RunSQL",
            "enabled" => true,
            'script'  => "
              DROP TABLE IF EXISTS $tmpTable;
              CREATE TABLE $tmpTable (c1 varchar(255), c2 varchar(255), c3 varchar(255));
              INSERT INTO $tmpTable (c1, c2, c3) VALUES ('a', 'b b', '  c\" ');
              INSERT INTO $tmpTable (c1, c2, c3) VALUES ('', ' ', '\t');
              INSERT INTO $tmpTable (c1, c2, c3) VALUES ('€', 'éáó', 'ä ö ü');
            ",
          ],
          [
            "type"           => "CRM_Sqltasks_Action_CSVExport",
            "enabled"        => true,
            "table"          => $tmpTable,
            "encoding"       => "UTF-8",
            "delimiter"      => ";",
            "enclosure_mode" => $mode,
            "headers"        => "Column 1=c1\r\nColumn 2=c2\r\nColumn 3=c3",
            "filename"       => basename($outputFilename),
            "path"           => dirname($outputFilename),
            "email"          => "",
            "email_template" => "1",
            "upload"         => "",
          ],
          [
            "type"    => "CRM_Sqltasks_Action_PostSQL",
            "enabled" => true,
            "script"  => "DROP TABLE IF EXISTS $tmpTable;",
          ],
        ],
      ];

      $this->createAndExecuteTask($taskConfig);

      $this->assertFileEquals(
        __DIR__ . "/../../../../fixtures/csvexport_enclosure_${mode}.csv",
        $outputFilename
      );
    }
  }

}
