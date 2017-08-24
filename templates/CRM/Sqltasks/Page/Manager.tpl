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
tr.sqltasks-plugin-disabled {
  color: lightgray;
}
{/literal}
</style>

<div id="help">
  {if $tasks}
    {ts}This is the list of currently configured tasks{/ts}
  {else}
    {ts}It looks like you're new here. This is the control center for all you SQL based scheduled tasks, but there is no task configured yet.{/ts}
  {/if}
  <a href="{crmURL p="civicrm/sqltasks/configure" q="reset=1&tid=new"}">{ts}Add a new one.{/ts}</a>
</div>
<table class="display" id="option11">
  <thead>
    <tr>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Name{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Description{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Schedule{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Enabled?{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Last Execution{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Next Execution{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Selection Order{/ts}</th>
      <th class="sorting_disabled" rowspan="1" colspan="1"></th>
    </tr>
  </thead>
  <tbody>
  {foreach from=$tasks item=task}
    <tr class="{cycle values="odd-row,even-row"} {if not $task.enabled}sqltasks-plugin-disabled{/if}">
      {assign var=task_id value=$task.id}
      <td>{$task.name}</td>
      <td>{$task.description}</td>
      <td>{$task.schedule}</td>
      <td>{$task.last_executed}</td>
      <td>{$task.next_execution}</td>
      <td>{if $task.enabled}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
      <td>
        <a class="crm-weight-arrow" href="{crmURL p='civicrm/sqltasks/manager' q="top=$task_id"}"><img src="{$config->resourceBase}i/arrow/first.gif" title="Move to top" alt="Move to top" class="order-icon"></a>&nbsp;
        <a class="crm-weight-arrow" href="{crmURL p='civicrm/sqltasks/manager' q="up=$task_id"}"><img src="{$config->resourceBase}i/arrow/up.gif" title="Move up one row" alt="Move up one row" class="order-icon"></a>&nbsp;
        <a class="crm-weight-arrow" href="{crmURL p='civicrm/sqltasks/manager' q="down=$task_id"}"><img src="{$config->resourceBase}i/arrow/down.gif" title="Move down one row" alt="Move down one row" class="order-icon"></a>&nbsp;
        <a class="crm-weight-arrow" href="{crmURL p='civicrm/sqltasks/manager' q="bottom=$task_id"}"><img src="{$config->resourceBase}i/arrow/last.gif" title="Move to bottom" alt="Move to bottom" class="order-icon"></a>
      </td>
      <td>
        <span class="btn-slide crm-hover-button">{ts}Actions{/ts}
          <ul class="panel">
            <li>
              {if $task.enabled}
                <a href="{crmURL p='civicrm/sqltasks/manager' q="disable=$task_id"}" class="action-item crm-hover-button delete-contact small-popup" title="{ts}Disable{/ts}">{ts}Disable{/ts}</a>
              {else}
                <a href="{crmURL p='civicrm/sqltasks/manager' q="enable=$task_id"}" class="action-item crm-hover-button delete-contact small-popup" title="{ts}Enable{/ts}">{ts}Enable{/ts}</a>
              {/if}
              <a href="{crmURL p='civicrm/banking/configure' q="reset=1&pid=$task_id"}" class="action-item crm-hover-button delete-contact small-popup" title="{ts}Configure{/ts}">{ts}Configure{/ts}</a>
              <a href="{crmURL p='civicrm/sqltasks/manager' q="delete=$task_id"}" class="action-item crm-hover-button delete-contact small-popup" title="{ts}Delete{/ts}">{ts}Delete{/ts}</a>
            </li>
          </ul>
        </span>
      </td>
    </tr>
  {/foreach}
  </tbody>
</table>