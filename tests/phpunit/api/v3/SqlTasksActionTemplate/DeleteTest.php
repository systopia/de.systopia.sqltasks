<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * SqltasksActionTemplate.Delete API Test Case
 *
 * @group headless
 */
class api_v3_SqltasksActionTemplate_DeleteTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
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
    try {
      $templatesCountBefore = $this->callAPISuccessGetCount('SqltasksActionTemplate', []);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltasksActionTemplate.get returns exception:" . $e->getMessage());
    }

    try {
      $templateFromApi = civicrm_api3('SqltasksActionTemplate', 'create', [
        "name"      => "Test Template",
        "type"      => "CRM_Sqltasks_Action_RunSQL",
        "config"    => '{"script":"aaaa --- test"}',
      ]);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltasksActionTemplate.create returns exception:" . $e->getMessage());
    }
    $this->assertTrue(isset(reset($templateFromApi['values'])["id"]), "Template ID should be set");

    try {
      civicrm_api3('SqltasksActionTemplate', 'delete', [ "id" => reset($templateFromApi['values'])['id']]);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltasksActionTemplate.delete returns exception:" . $e->getMessage());
    }

    try {
      $templatesCountAfterDelete = $this->callAPISuccessGetCount('SqltasksActionTemplate', []);
    } catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals(false, true, "SqltasksActionTemplate.get returns exception:" . $e->getMessage());
    }

    $this->assertEquals(
      $templatesCountBefore,
      $templatesCountAfterDelete ,
      "There should be exactly " . $templatesCountBefore . " template in the database. But exist - " . $templatesCountAfterDelete
    );
  }

}
