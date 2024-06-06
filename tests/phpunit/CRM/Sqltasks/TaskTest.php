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

  public function setUp() : void {
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_sqltasks WHERE 1');
    parent::setUp();
  }

  public function tearDown() : void {
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS tmp_test_execute');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS tmp_test_execute_post');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS tmp_test_input_value');
    parent::tearDown();
  }

  /**
   * Test that tasks are created and fields are set correctly
   */
  public function testCreateTask() {
    $main_sql = "
      DROP TABLE IF EXISTS tmp_test_task;
      CREATE TABLE IF NOT EXISTS tmp_test_task AS SELECT 1 AS contact_id;
    ";

    $post_sql = "DROP TABLE IF EXISTS tmp_test_task;";

    $data = [
      'name'          => 'testCreateTask',
      'description'   => 'Test Task Description',
      'category'      => 'Test Task Category',
      'scheduled'     => 'monthly',
      'parallel_exec' => 0,
      'config' => [
        'version' => 2,
        'actions' => [
          [
            'type'    => 'CRM_Sqltasks_Action_RunSQL',
            'script'  => $main_sql,
            'enabled' => TRUE,
          ],
          [
            'type'    => 'CRM_Sqltasks_Action_PostSQL',
            'script'  => $post_sql,
            'enabled' => TRUE,
          ],
        ],
      ],
    ];

    $task = new CRM_Sqltasks_BAO_SqlTask();
    $task->updateAttributes($data);

    $query = CRM_Core_DAO::executeQuery("
      SELECT * FROM civicrm_sqltasks WHERE name = 'testCreateTask'
    ");

    $query->fetch();
    $this->assertEquals('testCreateTask', $query->name);
    $this->assertEquals('Test Task Description', $query->description);
    $this->assertEquals('Test Task Category', $query->category);
    $this->assertEquals('monthly', $query->scheduled);
    $this->assertEquals(0, $query->parallel_exec);
    $this->assertStringContainsString('DROP TABLE IF EXISTS tmp_test_task', $query->config);
    $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS tmp_test_task AS SELECT 1 AS contact_id', $query->config);
    $this->assertStringContainsString('DROP TABLE IF EXISTS tmp_test_task', $query->config);
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
      'config' => [
        'version' => 2,
        'actions' => [
          [
            'type'    => 'CRM_Sqltasks_Action_RunSQL',
            'script'  => 'CREATE TABLE IF NOT EXISTS tmp_test_task AS SELECT 1 AS contact_id',
            'enabled' => TRUE,
          ],
          [
            'type'    => 'CRM_Sqltasks_Action_PostSQL',
            'script'  => 'DROP TABLE IF EXISTS tmp_test_task',
            'enabled' => TRUE,
          ],
        ],
      ],
    ];

    $task = new CRM_Sqltasks_BAO_SqlTask();
    $task->updateAttributes($data);

    $data = [
      'name'          => 'testUpdateTask2',
      'description'   => 'Test Task Description 2',
      'category'      => 'Test Task Category 2',
      'scheduled'     => 'daily',
      'parallel_exec' => 1,
      'config' => [
        'version' => 2,
        'actions' => [
          [
            'type'    => 'CRM_Sqltasks_Action_RunSQL',
            'script'  => 'CREATE TABLE IF NOT EXISTS tmp_test_task_2 AS SELECT 1 AS contact_id',
            'enabled' => TRUE,
          ],
          [
            'type'    => 'CRM_Sqltasks_Action_PostSQL',
            'script'  => 'DROP TABLE IF EXISTS tmp_test_task_2',
            'enabled' => TRUE,
          ],
        ],
      ],
    ];

    $task->updateAttributes($data);

    $query = CRM_Core_DAO::executeQuery("
      SELECT * FROM civicrm_sqltasks WHERE name = 'testUpdateTask2'
    ");

    $query->fetch();
    $this->assertEquals('testUpdateTask2', $query->name);
    $this->assertEquals('Test Task Description 2', $query->description);
    $this->assertEquals('Test Task Category 2', $query->category);
    $this->assertEquals('daily', $query->scheduled);
    $this->assertEquals(1, $query->parallel_exec);
    $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS tmp_test_task_2 AS SELECT 1 AS contact_id', $query->config);
    $this->assertStringContainsString('DROP TABLE IF EXISTS tmp_test_task_2', $query->config);
  }

  /**
   * Test that a task executes its actions and updates some fields
   *
   * @throws \Exception
   */
  public function testExecuteTask() {
    $main_sql = "
      DROP TABLE IF EXISTS tmp_test_execute;
      CREATE TABLE tmp_test_execute AS SELECT 1 AS contact_id;
    ";

    $post_sql = "
      DROP TABLE IF EXISTS tmp_test_execute_post;
      CREATE TABLE tmp_test_execute_post AS SELECT 1 AS contact_id;
    ";

    $data = [
      'name'    => 'testExecuteTask',
      'config' => [
        'version' => 2,
        'actions' => [
          [
            'type'    => 'CRM_Sqltasks_Action_RunSQL',
            'script'  => $main_sql,
            'enabled' => TRUE,
          ],
          [
            'type'    => 'CRM_Sqltasks_Action_PostSQL',
            'script'  => $post_sql,
            'enabled' => TRUE,
          ],
        ],
      ],
    ];

    $this->createAndExecuteTask($data);

    $this->assertLogContains("Action 'Run SQL Script' executed in");
    $this->assertLogContains("Action 'Run Cleanup SQL Script' executed in");

    $query = CRM_Core_DAO::executeQuery("
      SELECT * FROM civicrm_sqltasks WHERE name = 'testExecuteTask'
    ");

    $query->fetch();
    $this->assertGreaterThan(0, $query->last_runtime);

    $db_now = strtotime(CRM_Core_DAO::singleValueQuery("SELECT NOW()"));
    $last_exec_time = strtotime($query->last_execution);
    $this->assertLessThanOrEqual(1, abs($db_now - $last_exec_time), 'Task should have been executed recently');

    $executed = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM tmp_test_execute");
    $this->assertEquals(1, $executed, 'Table and row from Main SQL should have been created');

    $executed = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM tmp_test_execute_post");
    $this->assertEquals(1, $executed, 'Table and row from Post SQL should have been created');
  }

  /**
   * Test that a task with invalid SQL produces errors in logs
   */
  public function testFailTask() {
    $config = [
      'version' => 2,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'script'  => 'totally valid SQL # not',
          'enabled' => TRUE,
        ],
        [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'script'  => 'also valid # not',
          'enabled' => TRUE,
        ],
      ],
    ];

    $this->createAndExecuteTask([ 'config' => $config ]);
    $this->assertLogContains("Error in action 'Run SQL Script'");
    $this->assertLogContains("Error in action 'Run Cleanup SQL Script'");
  }

  /**
   * Test that execution of the task stops if the "abort_on_error"-flag is set
   * and an error occurs
   */
  public function testAbortOnError () {
    // Configure task
    $data = [
      'abort_on_error' => TRUE,
      'config' => [
        'version' => 2,
        'actions' => [
          [
            'type'    => 'CRM_Sqltasks_Action_RunSQL',
            'enabled' => TRUE,
            'script'  => 'SELECT 1',
          ],
          [
            'type'    => 'CRM_Sqltasks_Action_RunSQL',
            'enabled' => TRUE,
            'script'  => 'INSERT INTO no_such_table (id) VALUES (2)',
          ],
          [
            'type'    => 'CRM_Sqltasks_Action_RunSQL',
            'enabled' => TRUE,
            'script'  => 'SELECT 3',
          ],
          [
            'type'           => 'CRM_Sqltasks_Action_ErrorHandler',
            'enabled'        => TRUE,
            'email'          => 'errorhandler@example.com',
            'email_template' => '1',
          ],
        ],
      ],
    ];

    // Execute task
    $this->createAndExecuteTask($data);

    // Count action results
    $action_results_count = [
      'success' => 0,
      'error'   => 0,
      'skipped' => 0,
    ];

    $error_handler_executed = FALSE;

    foreach ($this->log as $log_entry) {
      if (strpos($log_entry, "Action 'Run SQL Script' executed") !== FALSE) {
        $action_results_count['success']++;
      }

      if (strpos($log_entry, "Error in action 'Run SQL Script'") !== FALSE) {
        $action_results_count['error']++;
      }

      if (strpos($log_entry, "Skipped 'Run SQL Script' due to previous error") !== FALSE) {
        $action_results_count['skipped']++;
      }

      if (strpos($log_entry, "Action 'Error Handler' executed") !== FALSE) {
        $error_handler_executed = TRUE;
      }
    }

    // Assert that the third action was skipped
    $this->assertEquals(1, $action_results_count['success'], 'Exactly 1 action should have been successful');
    $this->assertEquals(1, $action_results_count['error'], 'Exactly 1 action should have failed');
    $this->assertEquals(1, $action_results_count['skipped'], 'Exactly 1 action should have been skipped');
    $this->assertTrue($error_handler_executed, 'Error Handler should have been executed');
  }

  /**
   * Test that (global) tokens are replaced with their values
   */
  public function testGlobalTokens() {
    $tmp = tempnam(sys_get_temp_dir(), 'csv');

    // Add a global token setting that will be available via {config.*}
    (CRM_Sqltasks_GlobalToken::singleton())->setValue('test', 'expected_config_value');

    // Add context and setting token to file
    $tmp .= '_{context.input_val}_{setting.lcMessages}.csv';

    $sql = "
      DROP TABLE IF EXISTS tmp_test_input_value;
      CREATE TABLE tmp_test_input_value AS
        SELECT
          @input AS foo,
          '{context.random}' AS random,
          '{setting.lcMessages}' AS language,
          '{config.test}' AS config;
    ";

    $data = [
      'input_required' => TRUE,
      'config' => [
        'version'        => 2,
        'actions'        => [
          [
            'type'    => 'CRM_Sqltasks_Action_RunSQL',
            'script'  => $sql,
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
      ],
    ];

    $this->createAndExecuteTask( $data, [ 'input_val' => 'expected_value' ]);

    $this->assertLogContains("Action 'Run SQL Script' executed in");
    $this->assertLogContains('Written 1 records to', 'Records should have been written to CSV');
    $this->assertLogContains("Action 'CSV Export' executed in", 'CSV Export action should have succeeded');

    $actual_value = CRM_Core_DAO::singleValueQuery("SELECT foo FROM tmp_test_input_value");
    $this->assertEquals('expected_value', $actual_value, 'Table should contain the value passed via input_value');

    $random = CRM_Core_DAO::singleValueQuery("SELECT random FROM tmp_test_input_value");
    $this->assertEquals(16, strlen($random), 'Column "random" should contain 16 random characters');

    $language = CRM_Core_DAO::singleValueQuery("SELECT language FROM tmp_test_input_value");
    $this->assertEquals(Civi::settings()->get('lcMessages'), $language, 'Column "language" should match setting');

    $config = CRM_Core_DAO::singleValueQuery("SELECT config FROM tmp_test_input_value");
    $this->assertEquals((CRM_Sqltasks_GlobalToken::singleton())->getValue('test'), $config, 'Column "config" should match setting in sqltasks_global_tokens');

    $tmp = str_replace(
      ['{context.input_val}', '{setting.lcMessages}'],
      ['expected_value', Civi::settings()->get('lcMessages')],
      $tmp
    );

    $this->assertFileEquals(__DIR__ . '/../../../fixtures/csvexport_input_val.csv', $tmp);
  }

  /**
   * Test that concurrent changes of task configurations are detected
   */
  public function testConcurrentChanges () {
    // Configure task
    $data = [
      'enabled' => 1,
      'config' => [
        'version' => 2,
        'actions' => [
          [
            'type'    => 'CRM_Sqltasks_Action_RunSQL',
            'enabled' => FALSE,
            'script'  => "SELECT 1",
          ],
        ],
      ],
    ];

    // Create task
    $task = new CRM_Sqltasks_BAO_SqlTask();
    $task->updateAttributes($data);
    $last_modified = $task->last_modified;

    // Update task after 1 second via API
    sleep(1);
    $data['config']['actions'][0]['enabled'] = TRUE;
    $api_call_failed = FALSE;

    try {
      civicrm_api3('Sqltask', 'create', [
        'id'            => $task->id,
        'config'        => $data['config'],
        'last_modified' => $last_modified,
      ]);
    } catch (CiviCRM_API3_Exception $exception) {
      $api_call_failed = TRUE;
    }

    // Assert that the change has been applied as expected
    $this->assertEquals(false, $api_call_failed, "API call should have succeeded");

    $task = CRM_Sqltasks_BAO_SqlTask::findById($task->id);
    $first_action = json_decode($task->config, TRUE)['actions'][0];
    $this->assertEquals(1, $first_action["enabled"], 'Task config should have changed');

    // Update task after another second with now expired last_modified timestamp
    sleep(1);
    $data['config']['actions'][0]['enabled'] = TRUE;

    try {
      civicrm_api3("Sqltask", "create", [
        "id"            => $task->id,
        "config"        => $data['config'],
        "last_modified" => $last_modified,
      ]);
    } catch (CiviCRM_API3_Exception $exception) {
      $api_call_failed = TRUE;
    }

    // Assert that the change has not been applied due to a mismatch of last_modified timestamps
    $this->assertEquals(TRUE, $api_call_failed, "API call should have failed");

    $task = CRM_Sqltasks_BAO_SqlTask::findById($task->id);
    $first_action = json_decode($task->config, TRUE)['actions'][0];
    $this->assertEquals(1, $first_action["enabled"], "Task config should not have changed");
  }

}
