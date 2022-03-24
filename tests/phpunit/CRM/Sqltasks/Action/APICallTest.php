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

  public function testStoreApiResult() {
    $tmpContactTable = 'tmp_test_action_apicall';
    $tableRows = [];

    for ($i = 0; $i < 3; $i++) {
      $tableRows[] = [
        'contact_id' => self::createRandomTestContact(),
        'exclude'    => $i === 2 ? 1 : 0,
      ];
    }

    $phoneNumber = self::generateRandomPhoneNumber();

    $apiCallParams = [
      'contact_id' => '{contact_id}',
      'phone'      => $phoneNumber,
    ];

    $config = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        self::getCreateTempContactTableAction($tmpContactTable, $tableRows),
        [
          'type'              => 'CRM_Sqltasks_Action_APICall',
          'action'            => 'create',
          'enabled'           => TRUE,
          'entity'            => 'Phone',
          'parameters'        => self::serializeApiCallParams($apiCallParams),
          'store_api_results' => TRUE,
          'table'             => $tmpContactTable,
        ],
      ],
    ];

    $this->createAndExecuteTask($config);

    $phoneResult = $this->callApiSuccess('Phone', 'get', [
      'phone' => $phoneNumber,
    ]);

    $queryResult = CRM_Core_DAO::executeQuery(
      "SELECT `contact_id`, `sqltask_api_result`, `exclude` FROM `$tmpContactTable`"
    );

    while ($queryResult->fetch()) {
      $this->assertObjectHasAttribute(
        'sqltask_api_result',
        $queryResult,
        'Temporary table should have a api_result column'
      );

      if ((int) $queryResult->exclude) {
        $this->assertNull(
          $queryResult->sqltask_api_result,
          'Field sqltask_api_result should be null'
        );

        continue;
      }

      $this->assertNotNull(
        $queryResult->sqltask_api_result,
        'Field sqltask_api_result should not be null'
      );

      $apiResult = json_decode($queryResult->sqltask_api_result, TRUE);

      $this->assertArrayHasKey('is_error', $apiResult);

      if ($apiResult['is_error']) {
        trigger_error('API call failed', E_USER_WARNING);
      } else {
        $phoneProps = array_values($apiResult['values'])[0];

        $this->assertEquals(
          $queryResult->contact_id,
          $phoneProps['contact_id'],
          'API result should contain the original contact ID'
        );

        $this->assertEquals(
          $phoneNumber,
          $phoneProps['phone'],
          'API result should contain the original phone number'
        );
      }
    }

    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `$tmpContactTable`");
  }

  private static function generateRandomPhoneNumber() {
    $digits = "";

    for ($i = 0; $i < 12; $i++) {
      $digits .= (string) random_int(0, 9);
    }

    return trim(chunk_split($digits, 4, '-'), '-');
  }

  private static function serializeApiCallParams(array $params) {
    $paramPairs = [];

    foreach ($params as $key => $value) {
      $paramPairs[] = "$key=$value";
    }

    return join("\r\n", $paramPairs);
  }

}
