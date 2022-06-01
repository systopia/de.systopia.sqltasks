<?php

/**
 * Gets list of category (prepared for select)
 *
 * @return array
 */
function civicrm_api3_sqltaskfield_gettaskcategories() {
  $categoryOptions = [];
  $categories = CRM_Sqltasks_Task::getTaskCategoryList();

  foreach ($categories as $category) {
    $categoryOptions[$category] = $category;
  }

  return civicrm_api3_create_success([$categoryOptions]);
}
