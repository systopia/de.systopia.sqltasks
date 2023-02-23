<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * SqltasksActionTemplate.Create API Test Case
 *
 * @group headless
 */
class api_v3_SqltasksActionTemplate_CreateTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
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
   * Test creation of a template
   */
  public function testCreateTemplate() {
    try {
      $templatesCountBefore = $this->callAPISuccessGetCount('SqltasksActionTemplate', []);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltasksActionTemplate.get returns exception:" . $e->getMessage());
    }

    $templateData = [
      "name"      => "Test Template",
      "type"      => "CRM_Sqltasks_Action_RunSQL",
      "config"    => '{"script":"aaaa --- test"}'
    ];

    try {
      $templateFromApi = civicrm_api3('SqltasksActionTemplate', 'create', array_merge($templateData, ["sequential" => 1]));
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltasksActionTemplate.create returns exception:" . $e->getMessage());
    }

    $this->assertTrue(isset(reset($templateFromApi['values'])["id"]), "Template ID should be set");

    try {
      $templatesCountAfter = $this->callAPISuccessGetCount('SqltasksActionTemplate', []);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltasksActionTemplate.get returns exception:" . $e->getMessage());
    }

    $expectedTemplateCount = ($templatesCountBefore + 1);
    $this->assertEquals(
      ($templatesCountBefore + 1),
      $templatesCountAfter,
      "There should be exactly " . $expectedTemplateCount . " template in the database. But exist - " . $templatesCountAfter
    );

    try {
      $template = civicrm_api3("SqltasksActionTemplate", "get", [ "id" => reset($templateFromApi['values'])["id"]]);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltasksActionTemplate.get returns exception:" . $e->getMessage());
    }
    $this->assertTrue(!empty($template), "Cannot find Template by id = " . reset($templateFromApi['values'])["id"]);

    foreach (array_keys($templateData) as $property) {
      $this->assertEquals(
        $templateData[$property],
        reset($template["values"])[$property],
        sprintf("Template %s should be '%s'", $property, $templateData[$property])
      );
    }
  }

}
