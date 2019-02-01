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

{$form.task_id.html}{$form.enabled.html}{$form.weight.html}

<div class="sql-tasks">

  <h3>{ts}Basic Information{/ts}</h3>

  <div class="spacer"></div>

  <div class="crm-section form-item">
    <div class="label">{$form.name.label}</div>
    <div class="content">{$form.name.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section form-item">
    <div class="label">{$form.description.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Description{/ts}", {literal}{"id":"id-configure-description","file":"CRM\/Sqltasks\/Form\/Configure"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.description.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section form-item">
    <div class="label">{$form.category.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Category{/ts}", {literal}{"id":"id-configure-category","file":"CRM\/Sqltasks\/Form\/Configure"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.category.html}</div>
    <div class="clear"></div>
  </div>

  <h3>{ts}Queries{/ts}</h3>

  <div class="spacer"></div>

  <div class="crm-section form-item">
    <div class="label">{$form.main_sql.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Main Script{/ts}", {literal}{"id":"id-configure-main","file":"CRM\/Sqltasks\/Form\/Configure"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.main_sql.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section form-item">
    <div class="label">{$form.post_sql.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Clean Up{/ts}", {literal}{"id":"id-configure-post","file":"CRM\/Sqltasks\/Form\/Configure"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.post_sql.html}</div>
    <div class="clear"></div>
  </div>

  <h3>{ts}Execution{/ts}</h3>

  <div class="spacer"></div>

  <div class="crm-section">
    <div class="label">{$form.scheduled.label}</div>
    <div class="content">{$form.scheduled.html}</div>
    <div class="clear"></div>
  </div>

  <div id="advSchedule">
    <div id="advSchedule-weekday" class="crm-section">
      <div class="label">{$form.scheduled_weekday.label}</div>
      <div class="content">{$form.scheduled_weekday.html}</div>
      <div class="clear"></div>
    </div>

    <div id="advSchedule-day" class="crm-section">
      <div class="label">{$form.scheduled_day.label}</div>
      <div class="content">{$form.scheduled_day.html}</div>
      <div class="clear"></div>
    </div>

    <div id="advSchedule-hour" class="crm-section">
      <div class="label">{$form.scheduled_hour.label}</div>
      <div class="content">{$form.scheduled_hour.html}</div>
      <div class="clear"></div>
    </div>

    <div id="advSchedule-minute" class="crm-section">
      <div class="label">{$form.scheduled_minute.label}</div>
      <div class="content">{$form.scheduled_minute.html}</div>
      <div class="clear"></div>
    </div>

    <div class="description" class="crm-section">
      {ts}Set the exact <i>Weekday / Day / Hour / Minute</i> when the job will be executed, on the first cron call after this datetime.{/ts}
      <br />
    </div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.parallel_exec.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Parallel Execution{/ts}", {literal}{"id":"id-configure-parallel","file":"CRM\/Sqltasks\/Form\/Configure"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.parallel_exec.html}</div>
    <div class="clear"></div>
  </div>

  <h3>{ts}Actions{/ts}</h3>
  {foreach from=$action_list item=action key=action_id}
  <div class="crm-accordion-wrapper crm-sqltask-{$action_id} collapsed">
    {capture assign=enabledfield}{$action_id}_enabled{/capture}
    <div class="crm-accordion-header active">{$form.$enabledfield.html}&nbsp;{$form.$enabledfield.label}</div>
    <div class="crm-accordion-body">{include file=$action.tpl}</div>
  </div>
  {/foreach}
</div>

<br/>
<div id="help">
  {ts domain="de.systopia.sqltasks"}<strong>Caution!</strong> Be aware that these tasks can execute arbitrary SQL statements, which <i>can potentially destroy your database</i>. Only use this if you really know what you're doing, and always keep a backup of your database before experimenting.{/ts}
</div>


{* FOOTER *}
<br/>
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>


<!-- move to the right spot -->
{literal}
<script type="text/javascript">

// enable/disable actions
cj("input.crm-sqltask-action-enable").click(function(event) {
  // open accordeon
  var action = cj(this);
  if (action.prop('checked')) {
    action.closest("div.crm-accordion-wrapper").removeClass("collapsed");
  } else {
    action.closest("div.crm-accordion-wrapper").addClass("collapsed");
  }

  // stop further processing for this event
  event.stopPropagation();
});

// open all active task wrappers
cj("input.crm-sqltask-action-enable").each(function() {
  var action = cj(this);
  if (action.prop('checked')) {
    action.closest("div.crm-accordion-wrapper").removeClass("collapsed");
  }
});

function decodeHTML(selector) {
  var raw = cj(selector).val();
  var decoded = cj('<div/>').html(raw).text();
  cj(selector).val(decoded);
}

// decode HTML entities
decodeHTML("#main_sql");
decodeHTML("#post_sql");


CRM.$(function(){
  setControls(CRM.$('#scheduled').val());

  CRM.$('#scheduled').change(function(){
    setControls(CRM.$('#scheduled').val());
  });

  function setControls(value){
    switch(value){
    case "daily":
      CRM.$('#advSchedule').show();
      CRM.$('#advSchedule-weekday').hide();
      CRM.$('#advSchedule-day').hide();
      CRM.$('#advSchedule-hour').show();
      CRM.$('#advSchedule-min').show();
      break;
    case "hourly":
      CRM.$('#advSchedule').show();
      CRM.$('#advSchedule-weekday').hide();
      CRM.$('#advSchedule-day').hide();
      CRM.$('#advSchedule-hour').hide();
      CRM.$('#advSchedule-min').show();
      break;
    case "weekly":
      CRM.$('#advSchedule').show();
      CRM.$('#advSchedule-weekday').show();
      CRM.$('#advSchedule-day').hide();
      CRM.$('#advSchedule-hour').show();
      CRM.$('#advSchedule-min').show();
      break;
    case "monthly":
      CRM.$('#advSchedule').show();
      CRM.$('#advSchedule-weekday').hide();
      CRM.$('#advSchedule-day').show();
      CRM.$('#advSchedule-hour').show();
      CRM.$('#advSchedule-min').show();
      break;
    case "yearly":
    case "always":
      CRM.$('#advSchedule').hide();
      break;
    default:
      CRM.$('#advSchedule').hide();
      break;
    }
  }
});
</script>
{/literal}
