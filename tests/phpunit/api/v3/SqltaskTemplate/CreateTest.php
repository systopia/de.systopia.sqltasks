<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * SqltaskTemplate.Create API Test Case
 *
 * @group headless
 */
class api_v3_SqltaskTemplate_CreateTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
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
   * Test creation of a template
   */
  public function testCreateTemplate() {
    // Configure template
    $templateData = [
      "name"        => "Test-Template",
      "config"      => "{}",
      "description" => "...",
    ];

    // Create template via API
    civicrm_api3('SqltaskTemplate', 'create', $templateData);

    // Fetch templates from the database
    $templates = array_map(
      function ($bao) { return $bao->mapToArray(); },
      CRM_Sqltasks_BAO_SqltasksTemplate::getAll()
    );

    // Assert that there is exactly 1 template in the database
    $this->assertEquals(1, count($templates), "There should be exactly 1 template in the database");

    // Assert that the created template's properties match the expected values
    $this->assertTrue(isset($templates[0]["id"]), "Template ID should be set");
    $this->assertTrue(isset($templates[0]["last_modified"]), "'last_modified' timestamp should be set");

    foreach (["name", "config", "description"] as $property) {
      $this->assertEquals(
        $templateData[$property],
        $templates[0][$property],
        sprintf("Template %s should be '%s'", $property, $templateData[$property])
      );
    }
  }

}

?>
