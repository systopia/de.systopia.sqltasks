{*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2018 SYSTOPIA                            |
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

<div class="sql-tasks">
  <div class="crm-section">
    <div class="label">{$form.task_tasks.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Data Source{/ts}", {literal}{"id":"id-api-data","file":"CRM\/Sqltasks\/Action\/APICall"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.task_tasks.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.task_categories.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Entity{/ts}", {literal}{"id":"id-api-entity","file":"CRM\/Sqltasks\/Action\/APICall"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.task_categories.html}</div>
    <div class="clear"></div>
  </div>
</div>
