<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * SqltaskTemplate.Delete API Test Case
 *
 * @group headless
 */
class api_v3_SqltaskTemplate_DeleteTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  /**
   * Set up for headless tests.
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Test deletion of a template
   */
  public function testDeleteTemplate() {
    // Configure template
    $templateData = [
      "name"        => "Test-Template",
      "config"      => "{}",
      "description" => "...",
    ];

    // Create template via API
    civicrm_api3('SqltaskTemplate', 'create', $templateData);

    // Fetch templates from the database
    $templates = CRM_Sqltasks_BAO_SqltasksTemplate::getAll();

    // Assert that there is exactly 1 template in the database
    $this->assertEquals(1, count($templates), "There should be exactly 1 template in the database");

    // Delete the created template by ID
    civicrm_api3('SqltaskTemplate', 'delete', [ "id" => $templates[0]->id ]);

    // Fetch templates from the database
    $templates = CRM_Sqltasks_BAO_SqltasksTemplate::getAll();

    // Assert that there are no templates in the database
    $this->assertEquals(0, count($templates), "There should be no templates in the database");
  }

}

?>
