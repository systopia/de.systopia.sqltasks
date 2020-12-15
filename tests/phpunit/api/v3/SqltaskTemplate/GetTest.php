<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * SqltaskTemplate.Get API Test Case
 *
 * @group headless
 */
class api_v3_SqltaskTemplate_GetTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
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
   * Test getting a single template by ID
   */
  public function testGetTemplate() {
    // Configure template
    $templateData = [
      "name"        => "Test-Template",
      "config"      => "{}",
      "description" => "...",
    ];

    // Create template via API
    $createdTemplate = civicrm_api3("SqltaskTemplate", "create", $templateData)["values"];

    // Get template from API by ID
    $result = civicrm_api3("SqltaskTemplate", "get", [ "id" => $createdTemplate["id"] ])["values"];

    // Assert that the returned template matches the configured data
    $this->assertTrue(isset($result["id"]), "Template ID should be set");
    $this->assertTrue(isset($result["last_modified"]), "'last_modified' timestamp should be set");

    foreach (["name", "config", "description"] as $property) {
      $this->assertEquals(
        $templateData[$property],
        $result[$property],
        sprintf("Template %s should be '%s'", $property, $templateData[$property])
      );
    }
  }

}

?>
