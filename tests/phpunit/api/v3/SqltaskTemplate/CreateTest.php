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
    $templatesCountBefore = count(CRM_Sqltasks_BAO_SqltasksTemplate::getAll());

    $templateData = [
      "name"        => "Test-Template",
      "config"      => "{}",
      "description" => "...",
    ];

    try {
      $templateFromApi = civicrm_api3('SqltaskTemplate', 'create', $templateData);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltaskTemplate.create returns exception:" . $e->getMessage());
    }

    $this->assertTrue(isset($templateFromApi['values']["id"]), "Template ID should be set");
    $templatesCountAfter = count(CRM_Sqltasks_BAO_SqltasksTemplate::getAll());

    $expectedTemplateCount = ($templatesCountBefore + 1);
    $this->assertEquals(
      ($templatesCountBefore + 1),
      $templatesCountAfter,
      "There should be exactly " . $expectedTemplateCount . " template in the database. But exist - " . $templatesCountAfter
    );

    $template = CRM_Sqltasks_BAO_SqltasksTemplate::getOne($templateFromApi['values']["id"]);
    $this->assertTrue(!empty($template), "Cannot find Template by id = " . $templateFromApi['values']["id"]);

    foreach (array_keys($templateData) as $property) {
      $this->assertEquals(
        $templateData[$property],
        $template->$property,
        sprintf("Template %s should be '%s'", $property, $templateData[$property])
      );
    }
  }

}
