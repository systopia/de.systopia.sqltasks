<?php

trait CRM_Sqltasks_TempTableAlterations {

  /**
   * Add a column to the temporary data table in which the results of
   * API calls will be stored
   *
   * @param string $tableName  - Name of the table
   * @param string $columnName - Name of the result column to add
   *
   * @throws CRM_Core_Exception when trying to alter a civicrm_* table
   *
   * @return void
   */
  public function addApiResultColumn(string $tableName, string $columnName): void {
    if (strpos($tableName, 'civicrm_') === 0) {
      throw new CRM_Core_Exception("Cannot alter table $tableName");
    }

    $columnsResult = CRM_Core_DAO::executeQuery(
      "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'"
    );

    if ($columnsResult->fetch()) {
      $this->log("WARNING: Overwriting existing values for '$columnName' in '$tableName'");
      return;
    }

    CRM_Core_DAO::executeQuery("ALTER TABLE `$tableName` ADD `$columnName` TEXT");
  }

  /**
   * Add a auto_increment column to a table if one doesn't already exist
   *
   * @param string $tableName - Name of the table
   *
   * @throws CRM_Core_Exception when trying to alter a civicrm_* table
   *
   * @return string - Name of the auto_increment column
   */
  public static function addAutoIncrementColumn(string $tableName): string {
    if (strpos($tableName, 'civicrm_') === 0) {
      throw new CRM_Core_Exception("Cannot alter table $tableName");
    }

    $autoIncColResult = CRM_Core_DAO::executeQuery(
      "SHOW COLUMNS FROM `$tableName` WHERE `Extra` LIKE '%auto_increment%'"
    );

    if ($autoIncColResult->fetch()) {
      return $autoIncColResult->Field;
    }

    $aiColName = 'sqltask_row_id';

    CRM_Core_DAO::executeQuery(
      "ALTER TABLE `$tableName` ADD `$aiColName` INT AUTO_INCREMENT PRIMARY KEY"
    );

    return $aiColName;
  }

}
