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

}
