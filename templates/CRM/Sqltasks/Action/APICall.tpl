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

<div class="sql-tasks">
  <div class="crm-section">
    <div class="label">{$form.api_table.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Data Source{/ts}", {literal}{"id":"id-api-data","file":"CRM\/Sqltasks\/Action\/APICall"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.api_table.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.api_entity.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Entity{/ts}", {literal}{"id":"id-api-entity","file":"CRM\/Sqltasks\/Action\/APICall"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.api_entity.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.api_action.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Action{/ts}", {literal}{"id":"id-api-action","file":"CRM\/Sqltasks\/Action\/APICall"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.api_action.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.api_parameters.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Columns{/ts}", {literal}{"id":"id-api-parameters","file":"CRM\/Sqltasks\/Action\/APICall"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.api_parameters.html}</div>
    <div class="clear"></div>
  </div>
</div>
