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

}
