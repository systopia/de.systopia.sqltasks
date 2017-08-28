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
    <p>{ts}Executing...{/ts}</p>
    <div align="center">
      <img src="{$config->resourceBase}i/loading-overlay.gif" width="64"/>
    </div>
  </div>
  <div class="sql-tasks task-results">
  </div>
</div>

{* BACK BUTTON *}
<span class="crm-button crm-icon-button">
  <!-- <span class="crm-button-icon ui-icon-check"></span> -->
  <div onClick="location.replace('{crmURL p='civicrm/sqltasks/manage'}');" title="{ts}Back to Manager{/ts}">{ts}Back to Manager{/ts}</div>
</span>

{* API CALL *}
<script type="text/javascript">
var task_id = {$task_id};
{literal}
cj(document).ready(function() {
  CRM.api3('Sqltask', 'execute', {"task_id": task_id})
    .done(function(result) {
      var log = result.values;
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
});
{/literal}
</script>