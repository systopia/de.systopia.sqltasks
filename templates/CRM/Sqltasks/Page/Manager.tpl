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

{* TODO: move to CSS file? *}
<style type="text/css">
{literal}
.ui-state-highlight {
  height: 30px;
  width: 100%;
  border: 2px solid #4999DA;
  margin: 0 0 0.75rem 0;
  position: relative;
  z-index: 6;
}

.handle {
  text-align: center;
  cursor: pointer;
  vertical-align: middle!important;
}
{/literal}
</style>

{* DELETION CONFIRMATION PAGE *}
{if $delete}
  <h3>{ts 1=$delete.name}Delete Plugin "%1"{/ts}</h3>
  <div>
    <p>
      {ts 1=$delete.name 2=$delete.id}You are about to delete plugin "%1" [%2]. You should consider simply disabling it, since all data will be lost.{/ts}
    </p>
    {assign var=plugin_id value=$delete.id}
    <a class="button" href="{crmURL p="civicrm/sqltasks/manage" q="reset=1&confirmed=1&delete=$plugin_id"}">
      <span><div class="icon ui-icon-trash css_left"></div>Delete</span>
    </a>
    <a class="button" href="{crmURL p="civicrm/sqltasks/manage"}">
      <span>Back</span>
    </a>
 </div>
{else}

{* NORMAL PAGE *}
<div id="help">
  {if $tasks}
    {ts}This is the list of currently configured tasks.{/ts}
  {else}
    {ts}It looks like you're new here. This is the control center for all you SQL based scheduled tasks, but there is no task configured yet.{/ts}
  {/if}
  {capture assign=add_url}{crmURL p="civicrm/sqltasks/configure" q="reset=1&tid=0"}{/capture}
  {capture assign=repo_url}https://github.com/systopia/de.systopia.sqltasks/blob/master/tasks/readme.md{/capture}
  {ts 1=$add_url 2=$repo_url}You might want to <a href="%1">ADD A NEW ONE</a>. Check out our <a href="%2" target="_blank">REPOSITORY</a> for examples to get you started.{/ts}</a>
</div>
<br/>

{* dispatcher frequency *}
<div id="help">
  {if $dispatcher_frequency eq 'Daily'}
    {ts domain="de.systopia.sqltasks"}The dispatcher is run <strong>every day</strong> after midnight. This is effectively the maximum frequency these taks are being executed with.{/ts}
  {elseif $dispatcher_frequency eq 'Hourly'}
    {ts domain="de.systopia.sqltasks"}The dispatcher is run <strong>every hour</strong> on the hour. This is effectively the maximum frequency these taks are being executed with.{/ts}
  {elseif $dispatcher_frequency eq 'Always'}
    {ts domain="de.systopia.sqltasks"}The dispatcher (and therefore all active tasks) will be triggered <strong>with every cron-run</strong>. Ask your administrator how often that is, in order to know the effective maximum frequency these taks are being executed with.{/ts}
  {else}
    {ts domain="de.systopia.sqltasks"}The dispatcher is currently <strong>disabled</strong>, none of the tasks will be executed automatically.{/ts}
  {/if}
</div>
<br/>

<table class="display" id="option11">
  <thead>
    <tr>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Category{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}ID{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Name{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Description{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Enabled?{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Schedule{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Last Execution{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Last Runtime{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Selection Order{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1"></th>
      <th class="sorting_disabled" rowspan="1" colspan="1"></th>
    </tr>
  </thead>
  <tbody id="sortable-tasks">
  {foreach from=$tasks key=key item=task}
    <tr id="{$task.id}_{$key}" class="{cycle values="odd-row,even-row"} {if not $task.enabled}disabled{/if}">
      {assign var=task_id value=$task.id}
      <td>{$task.category}</div></td>
      <td>[{$task.id}]</td>
      <td>{$task.name}</td>
      <td><div title="{$task.description|escape}">{$task.short_desc|escape}</div></td>
      <td>{if $task.enabled}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
      <td>{$task.schedule}{if $task.parallel_exec}<br/><strong>{ts}(parallel){/ts}{/if}</td>
      <td>{$task.last_executed}</td>
      <td>{$task.last_runtime}</td>
      <td>
        <a class="crm-weight-arrow" href="{crmURL p='civicrm/sqltasks/manage' q="top=$task_id"}"><img src="{$config->resourceBase}i/arrow/first.gif" title="Move to top" alt="Move to top" class="order-icon"></a>&nbsp;
        <a class="crm-weight-arrow" href="{crmURL p='civicrm/sqltasks/manage' q="up=$task_id"}"><img src="{$config->resourceBase}i/arrow/up.gif" title="Move up one row" alt="Move up one row" class="order-icon"></a>&nbsp;
        <a class="crm-weight-arrow" href="{crmURL p='civicrm/sqltasks/manage' q="down=$task_id"}"><img src="{$config->resourceBase}i/arrow/down.gif" title="Move down one row" alt="Move down one row" class="order-icon"></a>&nbsp;
        <a class="crm-weight-arrow" href="{crmURL p='civicrm/sqltasks/manage' q="bottom=$task_id"}"><img src="{$config->resourceBase}i/arrow/last.gif" title="Move to bottom" alt="Move to bottom" class="order-icon"></a>
      </td>
      <td>
        <span class="btn-slide crm-hover-button">{ts}Actions{/ts}
          <ul class="panel">
            <li>
              <a href="{crmURL p='civicrm/sqltasks/run' q="tid=$task_id"}" class="action-item crm-hover-button sqltasks-job-run" title="{ts}Run the task manually{/ts}" data-inputrequired="{$task.input_required}">{ts}Run Now{/ts}</a>
            </li>
            <li>
              <a href="{crmURL p='civicrm/sqltasks/configure' q="reset=1&tid=$task_id"}" class="action-item crm-hover-button small-popup" title="{ts}Configure{/ts}">{ts}Configure{/ts}</a>
            </li>
            <li>
              {if $task.enabled}
                <a href="{crmURL p='civicrm/sqltasks/manage' q="disable=$task_id"}" class="action-item crm-hover-button small-popup" title="{ts}Disable for scheduled execution{/ts}">{ts}Disable{/ts}</a>
              {else}
                <a href="{crmURL p='civicrm/sqltasks/manage' q="enable=$task_id"}" class="action-item crm-hover-button small-popup" title="{ts}Enable for scheduled execution{/ts}">{ts}Enable{/ts}</a>
              {/if}
            </li>
            <li>
              <a href="{crmURL p='civicrm/sqltasks/manage' q="delete=$task_id"}" class="action-item crm-hover-button small-popup" title="{ts}Delete Task{/ts}">{ts}Delete{/ts}</a>
            </li>
            <li>
              <a href="{crmURL p='civicrm/sqltasks/manage' q="export=$task_id"}" class="action-item crm-hover-button small-popup" title="{ts}Export Configuration{/ts}">{ts}Export Config{/ts}</a>
            </li>
            <li>
              <a href="{crmURL p='civicrm/sqltasks/import' q="tid=$task_id"}" class="action-item crm-hover-button small-popup" title="{ts}Import Configuration{/ts}">{ts}Import Config{/ts}</a>
            </li>
          </ul>
        </span>
      </td>
      <td class="handle">
        <div>&#8693;</div>
      </td>
    </tr>
  {/foreach}
  </tbody>
</table>
<br/>
<div id="help">
  {ts domain="de.systopia.sqltasks"}<strong>Caution!</strong> Be aware that these tasks can execute arbitrary SQL statements, which <i>can potentially destroy your database</i>. Only use this if you really know what you're doing, and always keep a backup of your database before experimenting.{/ts}
</div>

{/if}

<script type="text/javascript">
// reset the URL
window.history.replaceState("", "", "{$baseurl}");
{literal}
CRM.$('.sqltasks-job-run').click(function(e) {
  e.preventDefault();
  var html = '';
  var inputrequired = CRM.$(this).data('inputrequired');

  if (inputrequired) {
    html =  '<form action="#" method="post">' +
              '<div class="crm-block crm-form-block">' +
                '<div class="crm-section">' +
                  '<div class="label">' +
                    '<label for="input_val_label">' +
                      '{/literal}{ts}Enter the value that will be passed to the @input variable{/ts}{literal} ' +
                      '<span class="crm-marker" title="{/literal}{ts}This field is required.{/ts}{literal}">*</span>' +
                    '</label>' +
                  '</div>' +
                  '<div class="content">' +
                    '<input name="input_val" type="text" id="input_val" class="huge crm-form-text required">' +
                  '</div>' +
                  '<div class="clear"></div>' +
                '</div>' +
              '</div>' +
            '</form>' ;
  }
  var href = CRM.$(this).attr('href');
  CRM.confirm({
    width: 600,
    message: {/literal}'{ts}Are you sure you want to run this task?{/ts}'{literal} + html
  }).on('crmConfirm:yes', function() {
    if (inputrequired) {
      var input_val = CRM.$('#input_val').val();
      if (input_val.length < 1) {
        CRM.alert({/literal}'{ts}Entered value that will be passed to the @input variable is empty{/ts}'{literal}, {/literal}'{ts}Warning{/ts}'{literal});
      }
      else {
        window.location.href = href + '&input_val=' + input_val;
      }
    }
    else {
      window.location.href = href + '&input_val=0';
    }
  });
});
{/literal}
</script>