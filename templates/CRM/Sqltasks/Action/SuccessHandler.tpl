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
  <div id="help" style="margin-left: 5px; margin-right: 5px;">
    {ts domain="de.systopia.sqltasks"}This action will be triggered after the <strong>successful</strong> execution of the task.{/ts}
  </div>

  <div class="crm-section">
    <div class="label">{$form.success_table.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Error Table{/ts}", {literal}{"id":"id-handler-error-table","file":"CRM\/Sqltasks\/Action\/ResultHandler"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.success_table.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.success_email.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Email{/ts}", {literal}{"id":"id-handler-email","file":"CRM\/Sqltasks\/Action\/ResultHandler"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.success_email.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.success_email_template.label}</div>
    <div class="content">{$form.success_email_template.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.success_always.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Execute Always{/ts}", {literal}{"id":"id-handler-always","file":"CRM\/Sqltasks\/Action\/ResultHandler"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.success_always.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.success_attach_log.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Attach Log{/ts}", {literal}{"id":"id-handler-attach-log","file":"CRM\/Sqltasks\/Action\/ResultHandler"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.success_attach_log.html}</div>
    <div class="clear"></div>
  </div>
</div>
