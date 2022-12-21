<div class="crm-block crm-form-block">
  <div class="sql-task__execution-logs-wrap">
    <div class="sql-task__view-logs-buttons">
      <a class="sql-task__view-logs-button crm-form-submit default crm-button crm-hover-button"
         href="{crmURL p='civicrm/sqltasks-execution/list' q='reset=1'}" title="{ts}Go to lis{/ts}">
        <span class="ui-button-icon ui-icon fa-undo"></span>
        <span class="ui-button-icon-space"> </span>
        <span>{ts}Back to list{/ts}</span>
      </a>
      <a class="sql-task__view-logs-button crm-form-submit default crm-button crm-hover-button"
         href="{$manageSqlTaskUrl}" title="{ts}Go manage task{/ts}">
        <span class="ui-button-icon ui-icon fa-pencil"></span>
        <span class="ui-button-icon-space"> </span>
        <span>{ts}Manage this sqltask{/ts}</span>
      </a>
    </div>

    <div>Logs:</div>

    <div>
        <ul>
            {foreach from=$logsTaskExecution item=logItem}
                <li>
                  <b>date:</b>
                  <span>{$logItem.date}</span>
                  <b>message:</b>
                  <span>{$logItem.message}</span>
                </li>
            {/foreach}
        </ul>
    </div>

  </div>
</div>

{literal}
<style>
.sql-task__execution-logs-wrap {
  padding: 20px;
}

.sql-task__view-logs-buttons {
  display: flex;
  gap: 10px;
  align-content: center;
  padding-top: 0;
  padding-bottom: 20px;
}

.sql-task__view-logs-button {
  display: flex !important;
  gap: 5px;
  align-content: center;
  align-items: center;
}

.sql-task__view-logs-button > .ui-button-icon {
  display: flex !important;
  align-content: center;
  align-items: center;
  margin: 0 !important;
}
</style>
{/literal}
