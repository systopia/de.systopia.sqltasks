<?php

use Civi\Test\Api3TestTrait;

/**
 * Base class for action tests
 *
 * @group headless
 */
abstract class CRM_Sqltasks_Action_AbstractActionTest extends CRM_Sqltasks_AbstractTaskTest {
  use Api3TestTrait;

  const TEST_CONTACT_SQL = "SELECT contact_id FROM civicrm_email WHERE email = 'john.doe@example.com';";

  /**
   * @var int
   */
  protected $contactId;

  public function setUp() {
    $this->contactId = $this->callApiSuccess('Contact', 'create', [
      'first_name'   => 'John',
      'last_name'    => 'Doe',
      'contact_type' => 'Individual',
      'email'        => 'john.doe@example.com',
    ])['id'];
    parent::setUp();
  }

  protected static function createRandomTestContact() {
    $uniqueID = bin2hex(random_bytes(8));

    $contactResult = civicrm_api3('Contact', 'create', [
      'first_name'   => 'Test',
      'last_name'    => $uniqueID,
      'contact_type' => 'Individual',
      'email'        => "test-$uniqueID@example.com",
    ]);

    return (int) $contactResult['id'];
  }

  protected static function getCreateTempContactTableAction(string $tableName, array $contactIDs) {
    $sql = "
      DROP TABLE IF EXISTS `$tableName`;
      CREATE TABLE `$tableName` (contact_id INT);
    ";

    foreach ($contactIDs as $contactID) {
      $sql .= "INSERT INTO `$tableName` VALUES ($contactID);\n";
    }

    return [
      'type'    => 'CRM_Sqltasks_Action_RunSQL',
      'enabled' => TRUE,
      'script'  => $sql,
    ];
  }

}
