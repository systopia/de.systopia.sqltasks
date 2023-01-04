<div class="sql-task__execution-view-wrap">
  <div class="crm-block crm-form-block">
    <div class="sql-task__execution-logs-wrap">
      <div class="sql-task__view-logs-buttons">
        <a class="sql-task__view-logs-button crm-form-submit default crm-button crm-hover-button"
           href="{crmURL p='civicrm/sqltasks-execution/list' q='reset=1'}" title="{ts}Go to lis{/ts}">
          <span class="ui-button-icon ui-icon fa-list"></span>
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

      <div>
        <div>
          <div><b>{$task.name}</b></div>
          <div>Task id={$taskId}.</div>
          <div class="sql-task__description">{$task.description}</div>
        </div>

        <div class="sql-task__info-item-wrap">
          <div class="sql-task__info-item">
            <div class="sql-task__info-item-title">Execution start date</div>
            <div class="sql-task__info-item-value">{$sqltasksExecution.start_date}</div>
          </div>

          <div class="sql-task__info-item">
            <div class="sql-task__info-item-title">Execution end date</div>
            <div class="sql-task__info-item-value">{$sqltasksExecution.end_date}</div>
          </div>

          <div class="sql-task__info-item">
            <div class="sql-task__info-item-title">Task runtime(milliseconds)</div>
            <div class="sql-task__info-item-value">{$sqltasksExecution.runtime} milliseconds</div>
          </div>
        </div>

        <div class="sql-task__info-item-wrap">
          <div class="sql-task__info-item">
            <div class="sql-task__info-item-title">Is executions has errors?</div>
            <div class="sql-task__info-item-value">{if $sqltasksExecution.is_has_errors} yes, errors count = {$sqltasksExecution.error_count}{else} no {/if}</div>
          </div>

          <div class="sql-task__info-item">
            <div class="sql-task__info-item-title">Task input value</div>
            <div class="sql-task__info-item-value">{$sqltasksExecution.input}</div>
          </div>

          <div class="sql-task__info-item">
            <div class="sql-task__info-item-title">Executor contact:</div>
            <div class="sql-task__info-item-value">
              <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$sqltasksExecution.id`"}"  target="_blank">{$sqltasksExecution.created_contact_display_name}</a>
            </div>
          </div>

          <div class="sql-task__info-item">
            <div class="sql-task__info-item-title">Task files:</div>
            <div class="sql-task__info-item-value">{$sqltasksExecution.files}</div>
          </div>
        </div>

      </div>

      <div>Execution logs:</div>

      <div class="sql-task__log-wrap">
          <ol>
              {foreach from=$logsTaskExecution item=logItem}
                  <li class="sql-task__log-item">
                    <span><b>{$logItem.date_time_obj->format("m-d-Y H:i:s.u")}:</b></span>
                    <span>{$logItem.message}</span>
                  </li>
              {/foreach}
          </ol>
      </div>

    </div>
  </div>
</div>

{literal}
<style>
.sql-task__execution-view-wrap > .crm-block.crm-form-block {
  box-shadow: none !important;
}

.sql-task__log-wrap {
  border: 1px solid #c1c1c1;
  margin-top: 10px;
  background: whitesmoke;
}

.sql-task__log-item {
  padding-bottom: 10px;
  max-width: 700px;
}

.sql-task__description {
  font-style: italic;
  max-width: 700px;
  padding-top: 10px;
}

.sql-task__info-item-wrap {
  display: flex;
  gap: 10px;
}

.sql-task__info-item {
  padding: 10px 0 10px 0;
  max-width: 250px;
  width: 250px;
}

.sql-task__info-item-title {
  font-weight: bold;
}

.sql-task__info-item-value {

}

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
