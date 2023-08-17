<?php

/**
 * Test RunPHP Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_ReturnValueTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function tearDown() {
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS tmp_return');
    parent::tearDown();
  }

  public function testReturnValue() {
    $data = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS `tmp_return`;
              CREATE TABLE `tmp_return` (
                `value` varchar(255) DEFAULT NULL COMMENT 'Return Value'
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
              INSERT INTO `tmp_return`
              (`value`)
              VALUES
              ('output');"
        ],
        [
          'type'     => 'CRM_Sqltasks_Action_ReturnValue',
          'enabled'  => TRUE,
          'table' => 'tmp_return',
          'parameter' => 'test'
        ],
      ]
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains("Action 'Return Value' executed in", 'Return Value action should have succeeded');

    $params = [
      'id' => 1,
      'log_to_file' => '',
      'input_val' => ''
    ];
    $result = $this->callApiSuccess('Sqltask', 'execute', $params);

    $this->assertArrayHasKey('test', $result['values']);
    $this->assertContains('output', $result['values']);
  }
}
