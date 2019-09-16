<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;

/**
 * Base class for task tests
 *
 * @group headless
 */
abstract class CRM_Sqltasks_AbstractTaskTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface {

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

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  protected function createAndExecuteTask(array $data) {
    $task = new CRM_Sqltasks_Task(NULL, $data);
    $task->store();
    $this->log = $task->execute();
    return $task;
  }

  protected function assertLogContains($expected, $message = '') {
    $this->assertContains($expected, implode("\n", $this->log), $message);
  }

}
