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

{literal}
<style type="text/css">
div.task-results {
    font-family: "Courier New", Courier, monospace;
}
</style>
{/literal}

{* BUSY / LOG VIEW *}
<div class="sql-tasks">
  <div class="sql-tasks task-running">
    {if $reload}
    <p>{ts}Page reload or browser back/forward navigation detected. This will not run the task again any more, to prevent accidental execution.{/ts}</p>
    <p>{ts}Please use the "run again" button below, if you want to execute this task <i>again</i>.{/ts}</p>
    {else}
    <p>{ts}Executing...{/ts}</p>
    <div align="center">
      <img src="{$config->resourceBase}i/loading-overlay.gif" width="64"/>
    </div>
    {/if}
  </div>
  <div class="sql-tasks task-results">
  </div>
</div>

{* BACK BUTTON *}
<a class="button" href="{crmURL p='civicrm/sqltasks/manage'}" title="{ts}Back to Manager{/ts}">
  {ts}Back to Manager{/ts}
</a>
<a class="button" href="{crmURL p='civicrm/sqltasks/run' q="tid=$task_id&input_val=$input_val_urlencoded"}" title="{ts}Run again{/ts}">
  {ts}Run again{/ts}
</a>
<a class="button" href="{crmURL p='civicrm/sqltasks/configure' q="tid=$task_id"}" title="{ts}Configure task{/ts}">
  {ts}Configure task{/ts}
</a>


{* API CALL *}
<script type="text/javascript">
// reset the URL ()
var reload_url = cj("<div>{$reload_url}</div>");
window.history.replaceState("", "", reload_url.text());

// run task (of this is not a page reload)
var task_id = {$task_id};
var input_val = '{$input_val}';
{if not $reload}
{literal}
CRM.api3('Sqltask', 'execute', {"task_id": task_id, "input_val": input_val})
  .done(function(result) {
    var log = result.values.log;
    var log_html = "<p><ul>";
    for (var i = 0; i < log.length; i++) {
      log_html += "<li>" + log[i] + "</li>";
    }
    log_html += "</ul></p>";
    cj("div.task-results").html(log_html);

    cj("div.task-running").hide();
    cj("div.task-results").show(200);
  }
);
{/literal}
{/if}
</script>