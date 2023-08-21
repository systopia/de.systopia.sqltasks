<div class="sql-task__settings-wrap">
  <div class="crm-block crm-form-block">

    <div class="sql-task__settings-buttons-wrap">
      <div class="sql-task__button-wrap">
        <a class="sql-task__button sql-task__search-button crm-form-submit default crm-button crm-hover-button"
           href="{crmURL p='civicrm/sqltasks/manage' q='reset=1'}" title="{ts}Go to the SQL Task Manager{/ts}">
          <span class="crm-i fa-list"></span>
          <span>{ts}Go to the SQL Task Manager{/ts}</span>
        </a>
      </div>
    </div>

    <div class="sql-task__settings">
      <div class="sql-task__settings-items">
        {foreach from=$settingsNames item=elementName}
          <div class="crm-section">
            <div class="label">{$form.$elementName.label}{help id="$elementName"}</div>
            <div class="content">{$form.$elementName.html}</div>
            <div class="clear"></div>
          </div>
        {/foreach}
      </div>
    </div>

    <div class="sql-task__settings-buttons-wrap crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
  </div>
</div>

{literal}
  <style>
      .sql-task__settings-button {
          float: none;
      }

      .sql-task__settings-buttons-wrap {
          display: flex;
          padding: 10px;
      }

      .sql-task__settings-items {
          padding: 20px 10px;
      }
  </style>
{/literal}
