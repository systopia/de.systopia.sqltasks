<?php

use Civi\Test\Api3TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;

/**
 * Base class for action tests
 *
 * @group headless
 */
abstract class CRM_Sqltasks_Action_AbstractActionTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface {
  use Api3TestTrait;

  const TEST_CONTACT_SQL = "SELECT contact_id FROM civicrm_email WHERE email = 'john.doe@example.com';";

  /**
   * @var int
   */
  protected $contactId;

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
    $this->contactId = $this->callApiSuccess('Contact', 'create', [
      'first_name'   => 'John',
      'last_name'    => 'Doe',
      'contact_type' => 'Individual',
      'email'        => 'john.doe@example.com',
    ])['id'];
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  protected function createAndExecuteTask(array $data) {
    $task = new CRM_Sqltasks_Task(NULL, $data);
    $task->store();
    $this->log = $task->execute();
  }

  protected function assertLogContains($expected, $message) {
    $this->assertContains($expected, implode("\n", $this->log), $message);
  }

}
