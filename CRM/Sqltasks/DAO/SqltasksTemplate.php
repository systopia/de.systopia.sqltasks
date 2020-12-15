<?php

/**
 * Database access object for the SqltasksTemplate entity.
 */
class CRM_Sqltasks_DAO_SqltasksTemplate extends CRM_Core_DAO {

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_sqltasks_template';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  public static $_log = TRUE;

  /**
   * Unique SqltasksTemplate ID
   *
   * @var int
   */
  public $id;

  /**
   * name of the template
   *
   * @var varchar(255)
   */
  public $name;

  /**
   * template description
   *
   * @var text
   */
  public $description;

  /**
   * configuration (JSON)
   *
   * @var text
   */
  public $config;

  /**
   * last time the template has been modified
   *
   * @var datetime
   */
  public $last_modified;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_sqltasks_template';
    parent::__construct();
  }

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => CRM_Sqltasks_ExtensionUtil::ts('Unique SqltasksTemplate ID'),
          'required' => TRUE,
          'where' => 'civicrm_sqltasks_template.id',
          'table_name' => 'civicrm_sqltasks_template',
          'entity' => 'SqltasksTemplate',
          'bao' => 'CRM_Sqltasks_DAO_SqltasksTemplate',
          'localizable' => 0,
        ],
        'name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => CRM_Sqltasks_ExtensionUtil::ts('Name'),
          'description' => CRM_Sqltasks_ExtensionUtil::ts('name of the template'),
          'where' => 'civicrm_sqltasks_template.name',
          'table_name' => 'civicrm_sqltasks_template',
          'entity' => 'SqltasksTemplate',
          'bao' => 'CRM_Sqltasks_DAO_SqltasksTemplate',
          'localizable' => 0,
        ],
        'description' => [
          'name' => 'description',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => CRM_Sqltasks_ExtensionUtil::ts('Description'),
          'description' => CRM_Sqltasks_ExtensionUtil::ts('template description'),
          'where' => 'civicrm_sqltasks_template.description',
          'table_name' => 'civicrm_sqltasks_template',
          'entity' => 'SqltasksTemplate',
          'bao' => 'CRM_Sqltasks_DAO_SqltasksTemplate',
          'localizable' => 0,
        ],
        'config' => [
          'name' => 'config',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => CRM_Sqltasks_ExtensionUtil::ts('Config'),
          'description' => CRM_Sqltasks_ExtensionUtil::ts('configuration (JSON)'),
          'where' => 'civicrm_sqltasks_template.config',
          'table_name' => 'civicrm_sqltasks_template',
          'entity' => 'SqltasksTemplate',
          'bao' => 'CRM_Sqltasks_DAO_SqltasksTemplate',
          'localizable' => 0,
        ],
        'last_modified' => [
          'name' => 'last_modified',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => CRM_Sqltasks_ExtensionUtil::ts('Last Modified'),
          'description' => CRM_Sqltasks_ExtensionUtil::ts('last time the template has been modified'),
          'where' => 'civicrm_sqltasks_template.last_modified',
          'table_name' => 'civicrm_sqltasks_template',
          'entity' => 'SqltasksTemplate',
          'bao' => 'CRM_Sqltasks_DAO_SqltasksTemplate',
          'localizable' => 0,
        ],
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return bool
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'sqltasks_template', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'sqltasks_template', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of indices
   *
   * @param bool $localize
   *
   * @return array
   */
  public static function indices($localize = TRUE) {
    $indices = [];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}
