<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * SqltaskTemplate.Getall API Test Case
 *
 * @group headless
 */
class api_v3_SqltaskTemplate_GetallTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
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
   * Test getting all stored templates
   */
  public function testGetAllTemplates() {
    $templatesCountBeforeCreating = count(CRM_Sqltasks_BAO_SqltasksTemplate::getAll());

    $countOfNewTemplates = 3;
    for ($i = 1; $i <= $countOfNewTemplates; $i++) {
      try {
        civicrm_api3("SqltaskTemplate", "create", [
          "name"        => "Test-Template #$i",
          "config"      => "{}",
          "description" => "...",
        ]);
      } catch (CiviCRM_API3_Exception $e) {
        $this->assertEquals(false, true, "SqltaskTemplate.create returns exception:" . $e->getMessage());
      }
    }

    try {
      $templatesFromApi = civicrm_api3("SqltaskTemplate", "get_all")["values"];
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltaskTemplate.get_all returns exception:" . $e->getMessage());
    }

    $expectedCountOfTemplates = $templatesCountBeforeCreating + $countOfNewTemplates;
    $this->assertEquals(
      $expectedCountOfTemplates,
      count($templatesFromApi),
      "Exactly " . $expectedCountOfTemplates . " templates should have been returned. But exist - " .  count($templatesFromApi)
    );
  }

}
