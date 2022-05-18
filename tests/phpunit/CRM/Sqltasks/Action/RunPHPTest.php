<?php

/**
 * Test RunPHP Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_RunPHPTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function tearDown() {
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS tmp_test_action_php');
    parent::tearDown();
  }

  public function testRunPHP() {
    $data = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_php;
                        CREATE TABLE tmp_test_action_php AS " . self::TEST_CONTACT_SQL,
        ],
        [
          'type'     => 'CRM_Sqltasks_Action_RunPHP',
          'enabled'  => TRUE,
          'php_code' => "CRM_Core_DAO::executeQuery('TRUNCATE TABLE tmp_test_action_php');"
        ],
      ]
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains("Action 'Run PHP Code' executed in", 'Run PHP action should have succeeded');
    $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(1) FROM tmp_test_action_php");
    $this->assertEquals(0, $count, 'Table tmp_test_action_php should have been truncated');
  }

}
