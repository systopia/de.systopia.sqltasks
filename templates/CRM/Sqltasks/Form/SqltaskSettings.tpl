<div class="sql-task__settings-wrap">
  <div class="crm-block crm-form-block">

    <div class="sql-task__settings-buttons-wrap">
      <a class="sql-task__settings-button button crm-button" crm-icon="fa-list" href="{$SqltaskManagerLink}" title="Back to Manager">
        <i class="crm-i fa-list" aria-hidden="true"></i>
        <span>SQL Task Manager</span>
      </a>
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
