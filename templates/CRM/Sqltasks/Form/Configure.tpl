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
    <div class="label">{$form.name.label}</div><!--a onclick='CRM.help("{ts domain="de.systopia.sqlsearch"}Instructions{/ts}", {literal}{"id":"id-token-help","file":"CRM\/moregreetings\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqlsearch"}Help{/ts}" class="helpicon">&nbsp;</a-->
    <div class="content">{$form.name.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.description.label}</div>
    <div class="content">{$form.description.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.pre_sql.label}</div>
    <div class="content">{$form.pre_sql.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.select_sql.label}</div>
    <div class="content">{$form.select_sql.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.post_sql.label}</div>
    <div class="content">{$form.post_sql.html}</div>
    <div class="clear"></div>
  </div>

  <hr/>



</div>


{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
