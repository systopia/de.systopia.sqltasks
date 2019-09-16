<?php

/**
 * Test APICall Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_APICallTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function testAPICall() {
    $data = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_apicall;
                        CREATE TABLE tmp_test_action_apicall AS " . self::TEST_CONTACT_SQL,
        ],
        [
          'type'       => 'CRM_Sqltasks_Action_APICall',
          'enabled'    => TRUE,
          'table'      => 'tmp_test_action_apicall',
          'entity'     => 'Phone',
          'action'     => 'create',
          'parameters' => "contact_id={contact_id}\r\nphone=1800testAPICall",
        ],
        [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_apicall;',
        ],
      ]
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains('1 API call(s) successfull.', '1 API call should have succeeded');
    $this->assertLogContains("Action 'API Call' executed in", 'API call action should have succeeded');
    $phoneCount = $this->callApiSuccess('Phone', 'getcount', [
      'contact_id' => $this->contactId,
      'phone'      => '1800testAPICall',
    ]);
    $this->assertEquals(1, $phoneCount, 'Phone should have been added to contact');
  }

  public function testExclude() {
    $contactIdExcluded = $this->callApiSuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'email'        => 'jane.doe@example.com',
    ])['id'];
    $data = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        [
          'type'    => 'CRM_Sqltasks_Action_RunSQL',
          'enabled' => TRUE,
          'script'  => "DROP TABLE IF EXISTS tmp_test_action_apicall;
                        CREATE TABLE tmp_test_action_apicall (contact_id INT(10), exclude BOOL, phone varchar(255));
                        INSERT INTO tmp_test_action_apicall SELECT contact_id, 0 as exclude, '1800testInclude' as phone FROM civicrm_email WHERE email='john.doe@example.com';
                        INSERT INTO tmp_test_action_apicall SELECT contact_id, 1 as exclude, '1800testExclude' as phone FROM civicrm_email WHERE email='jane.doe@example.com'",
        ],
        [
          'type'       => 'CRM_Sqltasks_Action_APICall',
          'enabled'    => TRUE,
          'table'      => 'tmp_test_action_apicall',
          'entity'     => 'Phone',
          'action'     => 'create',
          'parameters' => "contact_id={contact_id}\r\nphone={phone}",
        ],
        [
          'type'    => 'CRM_Sqltasks_Action_PostSQL',
          'enabled' => TRUE,
          'script'  => 'DROP TABLE IF EXISTS tmp_test_action_apicall;',
        ],
      ],
    ];
    $this->createAndExecuteTask($data);

    $this->assertLogContains('Column "exclude" exists, might skip some rows', '"exclude" column should have been detected');
    $this->assertLogContains('1 API call(s) successfull.', '1 API call should have succeeded');
    $this->assertLogContains("Action 'API Call' executed in", 'API call action should have succeeded');
    $phoneCount = $this->callApiSuccess('Phone', 'getcount', [
      'contact_id' => $this->contactId,
      'phone'      => '1800testInclude',
    ]);
    $this->assertEquals(1, $phoneCount, 'Phone should have been added to contact');
    $phoneCountExclude = $this->callApiSuccess('Phone', 'getcount', [
      'contact_id' => $contactIdExcluded,
      'phone'      => '1800testExclude',
    ]);
    $this->assertEquals(0, $phoneCountExclude, 'Excluded phone should not have been added to contact');
  }

}
