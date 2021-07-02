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
    $templatesCountBefore = count(CRM_Sqltasks_BAO_SqltasksTemplate::getAll());

    try {
      $templateFromApi = civicrm_api3('SqltaskTemplate', 'create', [
        "name"        => "Test-Template",
        "config"      => "{}",
        "description" => "...",
      ]);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltaskTemplate.create returns exception:" . $e->getMessage());
    }
    $this->assertTrue(isset($templateFromApi['values']["id"]), "Template ID should be set");

    try {
      civicrm_api3('SqltaskTemplate', 'delete', [ "id" => $templateFromApi['values']['id']]);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltaskTemplate.delete returns exception:" . $e->getMessage());
    }

    $templatesCountAfterDelete = count(CRM_Sqltasks_BAO_SqltasksTemplate::getAll());

    $this->assertEquals(
      $templatesCountBefore,
      $templatesCountAfterDelete ,
      "There should be exactly " . $templatesCountBefore . " template in the database. But exist - " . $templatesCountAfterDelete
    );
  }

}
