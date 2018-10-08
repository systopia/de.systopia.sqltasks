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
    <div class="label">{$form.activity_contact_table.label}</div>
    <div class="content">{$form.activity_contact_table.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_use_api.label}</div>
    <div class="content">{$form.activity_use_api.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_individual.label}</div>
    <div class="content">{$form.activity_individual.html}</div>
    <div class="clear"></div>
  </div>

  <hr/>

  <div class="spacer"></div>

  <div class="crm-section">
    <div class="label">{$form.activity_activity_type_id.label}</div>
    <div class="content">{$form.activity_activity_type_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_status_id.label}</div>
    <div class="content">{$form.activity_status_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_subject.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Tokens{/ts}", {literal}{"id":"id-activity-tokens","file":"CRM\/Sqltasks\/Action\/CreateActivity"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.activity_subject.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_details.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Tokens{/ts}", {literal}{"id":"id-activity-tokens","file":"CRM\/Sqltasks\/Action\/CreateActivity"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.activity_details.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_activity_date_time.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Timestamp Options{/ts}", {literal}{"id":"id-activity-datetime","file":"CRM\/Sqltasks\/Action\/CreateActivity"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.activity_activity_date_time.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_campaign_id.label}</div>
    <div class="content">{$form.activity_campaign_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_source_contact_id.label}</div>
    <div class="content">{$form.activity_source_contact_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_assigned_to.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Assignees{/ts}", {literal}{"id":"id-activity-assignees","file":"CRM\/Sqltasks\/Action\/CreateActivity"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.activity_assigned_to.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_source_record_id.label} <a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Tokens{/ts}", {literal}{"id":"id-activity-tokens","file":"CRM\/Sqltasks\/Action\/CreateActivity"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.activity_source_record_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_medium_id.label}</div>
    <div class="content">{$form.activity_medium_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_engagement_level.label}</div>
    <div class="content">{$form.activity_engagement_level.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_location.label} <a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Tokens{/ts}", {literal}{"id":"id-activity-tokens","file":"CRM\/Sqltasks\/Action\/CreateActivity"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.activity_location.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_duration.label} <a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Tokens{/ts}", {literal}{"id":"id-activity-tokens","file":"CRM\/Sqltasks\/Action\/CreateActivity"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.activity_duration.html} <span class="description">{ts}minutes{/ts}</span></div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.activity_priority_id.label}</div>
    <div class="content">{$form.activity_priority_id.html}</div>
    <div class="clear"></div>
  </div>

</div>
