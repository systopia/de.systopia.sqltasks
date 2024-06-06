<?php

/**
 * Test CallTask Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_CallTaskTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function tearDown() : void {
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS tmp_test_action_calltask');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS tmp_test_action_calltask_called');
    parent::tearDown();
  }

  public function testCallTask() {
    $data = [
      'name'     => 'calledTask',
      'enabled'  => 1,
      'config' => [
        'version'  => CRM_Sqltasks_Config_Format::CURRENT,
        'actions'  => [
          [
            'type'    => 'CRM_Sqltasks_Action_RunSQL',
            'enabled' => TRUE,
            'script'  => "DROP TABLE IF EXISTS tmp_test_action_calltask_called;
                          CREATE TABLE tmp_test_action_calltask_called AS " . self::TEST_CONTACT_SQL,
          ],
        ],
      ],
    ];

    $calledTask = new CRM_Sqltasks_BAO_SqlTask($data);
    $calledTask->save();

    $config = [
      'version'  => CRM_Sqltasks_Config_Format::CURRENT,
      'actions'  => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_calltask;
                        CREATE TABLE tmp_test_action_calltask AS " . self::TEST_CONTACT_SQL,
        ],
        [
          'type'       => 'CRM_Sqltasks_Action_CallTask',
          'enabled'    => TRUE,
          'tasks'      => [$calledTask->id],
          'categories' => [],
        ],
      ],
    ];

    $this->createAndExecuteTask([ 'config' => $config ]);

    $this->assertLogContains(
      "Executed task 'calledTask'",
      "Task 'calledTask' should have been executed"
    );
    $this->assertLogContains(
      "Action 'Run SQL Task(s)' executed in",
      'Run SQL Task(s) action should have succeeded'
    );
    $executed = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM tmp_test_action_calltask");
    $this->assertEquals(
      1,
      $executed,
      'Table and row from main task SQL script should have been created'
    );
    $executed = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM tmp_test_action_calltask_called");
    $this->assertEquals(
      1,
      $executed,
      'Table and row from called task SQL script should have been created'
    );
  }

}
