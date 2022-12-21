<div class="sql-task__execution-list-wrap">
  <div class="crm-block crm-form-block">
    <div class="sql-task__execution-list-search-wrap">
      <div class="crm-accordion-wrapper crm-contribution_search_form-accordion {if $isCollapseFilter}collapsed{/if}">
        <div class="crm-accordion-header crm-master-accordion-header">
            {ts}Filter{/ts}
        </div>
        <div class="crm-accordion-body">
          {strip}
            <div class="sql-task__search-block">

              <div class="sql-task__search-item-row">
                <div class="sql-task__search-item">
                    {$form.sqltask_id.label}<br/>
                    {$form.sqltask_id.html}
                </div>
              </div>

              <div class="sql-task__search-item-row">
                <div class="sql-task__search-item">
                    {$form.is_has_errors.label}<br/>
                    {$form.is_has_errors.html}
                </div>
                <div class="sql-task__search-item">
                    {$form.is_has_no_errors.label}<br/>
                    {$form.is_has_no_errors.html}
                </div>
              </div>

              <div class="sql-task__search-item-row">
                <div class="sql-task__search-item">
                    {$form.created_id.label}<br/>
                    {$form.created_id.html}
                </div>
              </div>

              <div class="sql-task__search-item-row">
                <div class="sql-task__search-item">
                    {$form.from_start_date.label}<br/>
                    {$form.from_start_date.html}
                </div>
                <div class="sql-task__search-item">
                    {$form.to_start_date.label}<br/>
                    {$form.to_start_date.html}
                </div>
              </div>

              <div class="sql-task__search-item-row">
                <div class="sql-task__search-item">
                    {$form.from_end_date.label}<br/>
                    {$form.from_end_date.html}
                </div>
                <div class="sql-task__search-item">
                    {$form.to_end_date.label}<br/>
                    {$form.to_end_date.html}
                </div>
              </div>

            </div>

            <div class="sql-task__search-buttons">
              <button class="sql-task__search-button crm-form-submit default crm-button crm-hover-button" value="1" type="submit" name="_qf_SqltasksExecutionList_submit">
                <span class="ui-button-icon ui-icon fa-check"></span>
                <span>Search</span>
              </button>

              <a class="sql-task__search-button crm-form-submit default crm-button crm-hover-button"
                 href="{crmURL p='civicrm/sqltasks-execution/list' q='reset=1'}" title="{ts}Clear all search criteria{/ts}">
                <span class="ui-button-icon ui-icon fa-undo"></span>
                <span>{ts}Reset Form{/ts}</span>
              </a>
            </div>
          </div>
        {/strip}
      </div>
    </div>

    <div class="crm-block crm-form-block">
      <table class="dataTable">
        <tr>
          <th>id</th>
          <th>task id</th>
          <th>error count</th>
          <th>input value</th>
          <th>Start date</th>
          <th>End date</th>
          <th>Executor</th>
          <th>actions</th>
        </tr>
        {foreach from=$sqltasksExecutions item=sqltasksExecution}
            <tr class="{if $sqltasksExecution.is_has_errors}sql-task__error-execution{else}sql-task__success-execution{/if}">
              <td>{$sqltasksExecution.id}</td>
              <td>{$sqltasksExecution.sqltask_id}</td>
              <td>{$sqltasksExecution.error_count}</td>
              <td>{$sqltasksExecution.input}</td>
              <td>{$sqltasksExecution.start_date}</td>
              <td>{$sqltasksExecution.end_date}</td>
              <td>{$sqltasksExecution.created_id}</td>
              <td>
                <a href="{crmURL p='civicrm/sqltasks-execution/view' q="reset=1&id=`$sqltasksExecution.id`"}" target="_blank">show logs</a>
              </td>
            </tr>
        {/foreach}
      </table>
    </div>
  </div>
</div>

{literal}
<style>
.sql-task__execution-list-search-wrap {
  padding: 20px;
}

.sql-task__search-buttons {
  display: flex;
  gap: 10px;
  align-content: center;
  padding-top: 20px;
}

.sql-task__search-button {
  display: flex !important;
  gap: 5px;
  align-content: center;
  align-items: center;
}
.sql-task__search-button > .ui-button-icon {
  display: flex !important;
  align-content: center;
  align-items: center;
  margin: 0 !important;
}

.sql-task__search-item-row {
  display: flex;
  gap: 20px;
}

.sql-task__search-item {
  padding-top: 10px;
}

.sql-task__execution-list-wrap table.dataTable tr.sql-task__success-execution {
  background: #d4e8be !important;
}

.sql-task__execution-list-wrap table.dataTable tr.sql-task__error-execution {
  background: #f6c5c5 !important;
}

</style>
{/literal}
