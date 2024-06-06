<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use PHPUnit\Framework\TestCase;

/**
 * Base class for task tests
 *
 * @group headless
 */
abstract class CRM_Sqltasks_AbstractTaskTest extends TestCase implements HeadlessInterface, HookInterface {

  /**
   * @var array
   */
  protected $log;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->uninstallMe(__DIR__)
      ->installMe(__DIR__)
      ->apply(TRUE);
  }

  public function setUp() : void {
    parent::setUp();
  }

  public function tearDown() : void {
    parent::tearDown();
  }

  protected function createAndExecuteTask(array $data, array $params = []) {
    $task = new CRM_Sqltasks_BAO_SqlTask();
    $task->updateAttributes($data);

    $taskExecutionResult = $task->execute($params);
    $this->log = $taskExecutionResult['logs'];

    return array_merge($taskExecutionResult, [ 'task' => $task ]);
  }

  protected function assertLogContains($expected, $message = '') {
    $this->assertStringContainsString($expected, implode("\n", $this->log), $message);
  }

}
