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
    // Configure templates
    $templatesData = array_map(
      function ($i) {
        return [
          "name"        => "Test-Template #$i",
          "config"      => "{}",
          "description" => "...",
        ];
      },
      [1, 2, 3]
    );

    // Create templates via API
    foreach ($templatesData as $data) {
      civicrm_api3("SqltaskTemplate", "create", $data);
    }

    // Get all templates from the API
    $templates = civicrm_api3("SqltaskTemplate", "get_all")["values"];

    // Assert that exactly 3 items have been returned
    $this->assertEquals(3, count($templates), "Exactly 3 templates should have been returned");

    // Sort results
    usort($templates, function ($a, $b) {
      return $a["id"] < $b["id"] ? -1 : 1;
    });

    // Assert that the returned templates match the configured data
    foreach ($templates as $i => $template) {
      $this->assertEquals(
        $templatesData[$i]["name"],
        $template["name"],
        sprintf("Template name should be '%s'", $templatesData[$i]["name"])
      );
    }
  }

}

?>
