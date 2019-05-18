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
        <td class="sqltask-mytask-name" title="{$task.description}">{$task.name}</td>
        <td class="sqltask-mytask-info">
            <span class="sqltask-mytask-runtime">{ts domain="de.systopia.sqltasks" 1=$task.last_runtime}%1 seconds{/ts}</span>
            <img class="sqltask-mytask-busy" src="{$config->resourceBase}i/loading.gif" style="display: none;"/>
        </td>
        <td class="sqltask-mytask-run">
            <a class="button sqltask-mytask-run" href="#" id="sqltask-{$task.id}" title="{ts domain="de.systopia.sqltasks" 1=$task.name}RUN %1{/ts}">
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
cj("a.sqltask-mytask-run").click(function(e) {
    // disable button
    cj(e.target).closest("a.button").addClass("disabled");

    // add spinner
    cj(e.target).closest("tr.sqltask-mytask").find("img.sqltask-mytask-busy").show();
    cj(e.target).closest("tr.sqltask-mytask").find("span.sqltask-mytask-runtime").hide();

    // clear donwnloads
    cj(e.target).closest("tr.sqltask-mytask").find("td.sqltask-mytask-downloads").children().addClass("disabled");

    // run task
    let task_id = e.target.id.substr(8);
    CRM.api3('Sqltask', 'execute', {"task_id": task_id})
        .done(function(result) {
            // compile log
            let log_text = "<ul>";
            for (let index in result.values.log) {
                log_text += "<li>" + result.values.log[index] + "</li>";
            }
            log_text += "</ul>";
            CRM.alert(log_text, "{/literal}{ts domain="de.systopia.mysqltasks"}Task Executed{/ts}{literal}", "info");

            // update downloads
            cj(e.target).closest("tr.sqltask-mytask").find("td.sqltask-mytask-downloads").children().remove();
            for (let index in result.values.files) {
                let file = result.values.files[index];
                cj(e.target)
                    .closest("tr.sqltask-mytask")
                    .find("td.sqltask-mytask-downloads")
                    .append('<a class="button sqltask-mytask-download" href="' + file['download_link'] + '" title="' + file['title'] + '">DOWNLOAD</a>');
            }

            // enable button
            cj(e.target).closest("a.button").removeClass("disabled");

            // set runtime
            cj(e.target)
                .closest("tr.sqltask-mytask")
                .find("span.sqltask-mytask-runtime")
                .text(result.values.runtime.toFixed(3) + " seconds")
                .show();

            // hide spinner
            cj(e.target).closest("tr.sqltask-mytask").find("img.sqltask-mytask-busy").hide();
        })
        .error(function(result) {
            console.log("ERROR");
            console.log(result);
        });
});
</script>
{/literal}
