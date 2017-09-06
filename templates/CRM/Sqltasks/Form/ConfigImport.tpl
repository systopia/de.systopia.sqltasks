{*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

{$form.task_id.html}

<div id="help">
  {ts domain="de.systopia.sqltasks"}This allows you to import configurations (<code>.sqltask</code> files) that you have previously exported. It will not update metadata like name, last run, enabled, or execution order.{/ts}
</div>

<div>
  <span>{$form.config_file.label}</span>
  <span>{$form.config_file.html}</span>
</div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
