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
  public function setUp() : void {
    parent::setUp();
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown() : void {
    parent::tearDown();
  }

  /**
   * Test getting a single template by ID
   */
  public function testGetTemplate() {
    $templateData = [
      "name"        => "Test-Template",
      "config"      => "{}",
      "description" => "...",
    ];

    try {
      $createdTemplateFromApi = civicrm_api3('SqltaskTemplate', 'create', $templateData);
    } catch (CRM_Core_Exception $e) {
      $this->assertEquals(false, true, "SqltaskTemplate.create returns exception:" . $e->getMessage());
    }

    try {
      $templateFromApi = civicrm_api3("SqltaskTemplate", "get", [ "id" => $createdTemplateFromApi["values"]["id"]]);
    } catch (CRM_Core_Exception $e) {
      $this->assertEquals(false, true, "SqltaskTemplate.get returns exception:" . $e->getMessage());
    }

    foreach (array_keys($templateData) as $property) {
      $this->assertEquals(
        $templateData[$property],
        $templateFromApi["values"][$property],
        sprintf("Template %s should be '%s'", $property, $templateData[$property])
      );
    }
  }

}
