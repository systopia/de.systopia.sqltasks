<?php

/**
 * Gets list of category (prepared for select)
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_sqltask_gettaskcategories() {
  $categoryOptions = [];
  $categories = CRM_Sqltasks_Task::getTaskCategoryList();

  foreach ($categories as $category) {
    $categoryOptions[$category] = $category;
  }

  return civicrm_api3_create_success([$categoryOptions]);
}
