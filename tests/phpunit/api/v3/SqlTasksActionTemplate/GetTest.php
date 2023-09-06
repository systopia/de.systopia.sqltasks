<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * SqltasksActionTemplate.Get API Test Case
 *
 * @group headless
 */
class api_v3_SqltasksActionTemplate_GetTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
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
  public function testGetActionTemplate() {
    $templateData = [
      "name"      => "Test Template",
      "type"      => "CRM_Sqltasks_Action_RunSQL",
      "config"    => '{"script":"aaaa --- test"}'
    ];

    try {
      $createdTemplateFromApi = civicrm_api3('SqltasksActionTemplate', 'create', array_merge($templateData, ["sequential" => 1]));
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltasksActionTemplate.create returns exception:" . $e->getMessage());
    }

    $action_template_id = reset($createdTemplateFromApi["values"])["id"];
    try {
      $templateFromApi = civicrm_api3("SqltasksActionTemplate", "get", [ "id" => $action_template_id ]);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltasksActionTemplate.get returns exception:" . $e->getMessage());
    }

    foreach (array_keys($templateData) as $property) {
      $this->assertEquals(
        $templateData[$property],
        reset($templateFromApi["values"])[$property],
        sprintf("Template %s should be '%s'", $property, $templateData[$property])
      );
    }
  }

}
