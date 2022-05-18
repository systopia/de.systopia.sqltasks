{*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2019 SYSTOPIA                            |
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

<table class="sqltask-mytask-list">
    {foreach from=$tasks key=key item=task}
    <tr class="sqltask-{$task.id} sqltask-mytask">
        <td class="sqltask-mytask-name" title="{$task.description}">
            {$task.name}
        </td>
        <td class="sqltask-mytask-info">
            <span class="sqltask-mytask-runtime">{ts domain="de.systopia.sqltasks" 1=$task.last_runtime}%1 seconds{/ts}</span>
            <img class="sqltask-mytask-busy" src="{$config->resourceBase}i/loading.gif"/>
        </td>
        <td>
            {if $task.input_required}
                <input class="crm-form-text sqltask-mytask-input-value" type="text">
            {/if}
        </td>
        <td class="sqltask-mytask-run">
            <a class="button sqltask-mytask-run"
               href="#"
               id="sqltask-{$task.id}"
               title="{ts domain="de.systopia.sqltasks" 1=$task.name}RUN %1{/ts}"
               data-task-id="{$task.id}"
               data-is-input-required="{$task.input_required}"
            >
                {ts domain="de.systopia.sqltasks"}RUN{/ts}
            </a>
        </td>
        <td class="sqltask-mytask-downloads">
        </td>
    </tr>
    {/foreach}
</table>

{literal}
<script type="text/javascript">
CRM.$(function ($) {
  $("a.sqltask-mytask-run").click(function(e) {
      let tasksButton = cj(e.target);
      let taskRow = tasksButton.closest("tr.sqltask-mytask");
      let taskId = tasksButton.data('task-id');
      let isInputRequired = tasksButton.data('is-input-required') == '1';
      let executeParams = {"task_id": taskId};

      if (isInputRequired) {
          let inputValue = taskRow.find('.sqltask-mytask-input-value').val();
          if (typeof inputValue === 'string' && inputValue.length > 0) {
              executeParams['input_val'] = inputValue;
          }
      }

      // disable button
      tasksButton.addClass("disabled");

      // add spinner
      taskRow.addClass('loading');

      // clear downloads
      taskRow.find("td.sqltask-mytask-downloads").children().addClass("disabled");

      // run task
      CRM.api3('Sqltask', 'execute', executeParams).done(function(result) {
          let isError = result.is_error === 1;

          // compile log
          if (isError) {
              CRM.alert(result.error_message, "{/literal}{ts domain="de.systopia.sqltasks"}Task execution error!{/ts}{literal}", "error");
          } else {
              let logText = "<ul>";
              for (let index in result.values.log) {
                  logText += "<li>" + result.values.log[index] + "</li>";
              }
              logText += "</ul>";
              CRM.alert(logText, "{/literal}{ts domain="de.systopia.sqltasks"}Task Executed{/ts}{literal}", "info");
          }

          // update downloads
          taskRow.find("td.sqltask-mytask-downloads").empty();
          if (!isError) {
              for (let index in result.values.files) {
                  let file = result.values.files[index];
                  taskRow.find("td.sqltask-mytask-downloads")
                      .append('<a class="button sqltask-mytask-download" href="' + file['download_link'] + '" title="' + file['title'] + '">DOWNLOAD</a>');
              }
          }

          // enable button
          tasksButton.removeClass("disabled");

          // set runtime
          let runtime = isError ? 0 : result.values.runtime;
          taskRow.find("span.sqltask-mytask-runtime").text(runtime.toFixed(3) + " seconds");

          // hide spinner
          taskRow.removeClass('loading');
      })
      .error(function(result) {
          console.error("ERROR");
          console.error(result);
      });
  });
});
</script>
{/literal}
