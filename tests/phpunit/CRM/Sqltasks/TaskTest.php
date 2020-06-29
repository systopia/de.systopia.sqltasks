<?php

use CRM_Sqltasks_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Tests overall task logic
 *
 * @group headless
 */
class CRM_Sqltasks_TaskTest extends CRM_Sqltasks_AbstractTaskTest {

  public function setUp() {
    CRM_Core_DAO::executeQuery('TRUNCATE TABLE civicrm_sqltasks');
    parent::setUp();
  }

  public function tearDown() {
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS tmp_test_execute');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS tmp_test_execute_post');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS tmp_test_input_value');
    parent::tearDown();
  }

  /**
   * Test that tasks are created and fields are set correctly
   */
  public function testCreateTask() {
    $main_sql = 'DROP TABLE IF EXISTS tmp_test_task; CREATE TABLE IF NOT EXISTS tmp_test_task AS SELECT 1 AS contact_id;';
    $post_sql = 'DROP TABLE IF EXISTS tmp_test_task;';
    $data = [
      'name'          => 'testCreateTask',
      'description'   => 'Test Task Description',
      'category'      => 'Test Task Category',
      'scheduled'     => 'monthly',
      'parallel_exec' => 0,
      'version' => 2,
      'actions' => [
        [
          'type' => 'CRM_Sqltasks_Action_RunSQL',
          'script' => $main_sql,
          'enabled' => TRUE,
        ],
        [
          'type' => 'CRM_Sqltasks_Action_PostSQL',
          'script' => $post_sql,
          'enabled' => TRUE,
        ],
      ],
    ];
    $task = new CRM_Sqltasks_Task(NULL, $data);
    $task->store();
    $query = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_sqltasks WHERE name = 'testCreateTask'");
    $query->fetch();
    $this->assertEquals('testCreateTask', $query->name);
    $this->assertEquals('Test Task Description', $query->description);
    $this->assertEquals('Test Task Category', $query->category);
    $this->assertEquals('monthly', $query->scheduled);
    $this->assertEquals(0, $query->parallel_exec);
    $this->assertContains($main_sql, $query->config);
    $this->assertContains($post_sql, $query->config);
  }

  /**
   * Test that existing tasks can be updated
   */
  public function testUpdateTask() {
    $data = [
      'name'          => 'testUpdateTask',
      'description'   => 'Test Task Description',
      'category'      => 'Test Task Category',
      'scheduled'     => 'monthly',
      'parallel_exec' => 0,
      'version' => 2,
      'actions' => [
        [
          'type' => 'CRM_Sqltasks_Action_RunSQL',
          'script' => 'CREATE TABLE IF NOT EXISTS tmp_test_task AS SELECT 1 AS contact_id',
          'enabled' => TRUE,
        ],
        [
          'type' => 'CRM_Sqltasks_Action_PostSQL',
          'script' => 'DROP TABLE IF EXISTS tmp_test_task',
          'enabled' => TRUE,
        ],
      ],
    ];
    $task = new CRM_Sqltasks_Task(NULL, $data);
    $task->store();
    $taskId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_sqltasks WHERE name = 'testUpdateTask'");
    $data = [
      'name'          => 'testUpdateTask2',
      'description'   => 'Test Task Description 2',
      'category'      => 'Test Task Category 2',
      'scheduled'     => 'daily',
      'parallel_exec' => 1,
      'version' => 2,
      'actions' => [
        [
          'type' => 'CRM_Sqltasks_Action_RunSQL',
          'script' => 'CREATE TABLE IF NOT EXISTS tmp_test_task_2 AS SELECT 1 AS contact_id',
          'enabled' => TRUE,
        ],
        [
          'type' => 'CRM_Sqltasks_Action_PostSQL',
          'script' => 'DROP TABLE IF EXISTS tmp_test_task_2',
          'enabled' => TRUE,
        ],
      ],
    ];
    $task = new CRM_Sqltasks_Task($taskId, $data);
    $task->store();
    $query = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_sqltasks WHERE name = 'testUpdateTask2'");
    $query->fetch();
    $this->assertEquals('testUpdateTask2', $query->name);
    $this->assertEquals('Test Task Description 2', $query->description);
    $this->assertEquals('Test Task Category 2', $query->category);
    $this->assertEquals('daily', $query->scheduled);
    $this->assertEquals(1, $query->parallel_exec);
    $this->assertContains('CREATE TABLE IF NOT EXISTS tmp_test_task_2 AS SELECT 1 AS contact_id', $query->config);
    $this->assertContains('DROP TABLE IF EXISTS tmp_test_task_2', $query->config);
  }

  /**
   * Test that a task executes its actions and updates some fields
   *
   * @throws \Exception
   */
  public function testExecuteTask() {
    $data = [
      'name'     => 'testExecuteTask',
      'version' => 2,
      'actions' => [
        [
          'type' => 'CRM_Sqltasks_Action_RunSQL',
          'script' => 'DROP TABLE IF EXISTS tmp_test_execute;
                       CREATE TABLE tmp_test_execute AS SELECT 1 AS contact_id;',
          'enabled' => TRUE,
        ],
        [
          'type' => 'CRM_Sqltasks_Action_PostSQL',
          'script' => 'DROP TABLE IF EXISTS tmp_test_execute_post;
                       CREATE TABLE tmp_test_execute_post AS SELECT 1 AS contact_id;',
          'enabled' => TRUE,
        ],
      ],
    ];
    $this->createAndExecuteTask($data);
    $this->assertLogContains("Action 'Run SQL Script' executed in");
    $this->assertLogContains("Action 'Run Cleanup SQL Script' executed in");
    $query = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_sqltasks WHERE name = 'testExecuteTask'");
    $query->fetch();
    $this->assertGreaterThan(0, $query->last_runtime);
    $dbNow = CRM_Core_DAO::singleValueQuery("SELECT NOW()");
    // this could, in theory, fail due to timing, but it hasn't so far, so #YOLO
    $this->assertEquals($dbNow, $query->last_execution, 'Task should have been executed recently');
    $executed = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM tmp_test_execute");
    $this->assertEquals(1, $executed, 'Table and row from Main SQL should have been created');
    $executed = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM tmp_test_execute_post");
    $this->assertEquals(1, $executed, 'Table and row from Post SQL should have been created');
  }

  /**
   * Test that a task with invalid SQL produces errors in logs
   */
  public function testFailTask() {
    $data = [
      'version' => 2,
      'actions' => [
        [
          'type' => 'CRM_Sqltasks_Action_RunSQL',
          'script' => 'totally valid SQL # not',
          'enabled' => TRUE,
        ],
        [
          'type' => 'CRM_Sqltasks_Action_PostSQL',
          'script' => 'also valid # not',
          'enabled' => TRUE,
        ],
      ],
    ];
    $this->createAndExecuteTask($data);
    $this->assertLogContains("Error in action 'Run SQL Script'");
    $this->assertLogContains("Error in action 'Run Cleanup SQL Script'");
  }

  /**
   * Test that (global) tokens are replaced with their values
   */
  public function testGlobalTokens() {
    $tmp = tempnam(sys_get_temp_dir(), 'csv');
    // add a global token setting that will be available via {config.*}
    (CRM_Sqltasks_GlobalToken::singleton())->setValue('test', 'expected_config_value');
    // add context and setting token to file
    $tmp .= '_{context.input_val}_{setting.lcMessages}.csv';
    $data = [
      'version'        => 2,
      'input_required' => TRUE,
      'actions'        => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'script'  => "DROP TABLE IF EXISTS tmp_test_input_value;
                        CREATE TABLE tmp_test_input_value AS
                        SELECT
                          @input AS foo,
                          '{context.random}' AS random,
                          '{setting.lcMessages}' AS language,
                          '{config.test}' AS config",
          'enabled' => TRUE,
        ],
        [
          'type'           => 'CRM_Sqltasks_Action_CSVExport',
          'enabled'        => TRUE,
          'table'          => 'tmp_test_input_value',
          'encoding'       => 'UTF-8',
          'delimiter'      => ';',
          'headers'        => "foo=foo",
          'filename'       => basename($tmp),
          'path'           => dirname($tmp),
          'email'          => '',
          'email_template' => '1',
          'upload'         => '',
        ],
      ],
    ];
    $this->createAndExecuteTask(
      $data,
      ['input_val' => 'expected_value']
    );
    $this->assertLogContains("Action 'Run SQL Script' executed in");
    $this->assertLogContains('Written 1 records to', 'Records should have been written to CSV');
    $this->assertLogContains("Action 'CSV Export' executed in", 'CSV Export action should have succeeded');
    $actualValue = CRM_Core_DAO::singleValueQuery("SELECT foo FROM tmp_test_input_value");
    $this->assertEquals(
      'expected_value',
      $actualValue,
      'Table should contain the value passed via input_value'
    );
    $random = CRM_Core_DAO::singleValueQuery("SELECT random FROM tmp_test_input_value");
    $this->assertEquals(
      16,
      strlen($random),
      'Column "random" should contain 16 random characters'
    );
    $language = CRM_Core_DAO::singleValueQuery("SELECT language FROM tmp_test_input_value");
    $this->assertEquals(
      Civi::settings()->get('lcMessages'),
      $language,
      'Column "language" should match setting'
    );
    $config = CRM_Core_DAO::singleValueQuery("SELECT config FROM tmp_test_input_value");
    $this->assertEquals(
      (CRM_Sqltasks_GlobalToken::singleton())->getValue('test'),
      $config,
      'Column "config" should match setting in sqltasks_global_tokens'
    );
    $tmp = str_replace(
      ['{context.input_val}', '{setting.lcMessages}'],
      ['expected_value', Civi::settings()->get('lcMessages')],
      $tmp
    );
    $this->assertFileEquals(__DIR__ . '/../../../fixtures/csvexport_input_val.csv', $tmp);
  }

}
