<div id="sqltasks-templates">
    <div class="help">
        <p>{ts}Here you can manage configuration templates for SQL Tasks.{/ts}</p>
        <p>{ts}The selected default template will be used whenever a user wants to create a new task.{/ts}</p>
    </div>

    <table class="dataTable">
        <thead>
            <tr class="columnheader">
                <th></th>
                <th>{ts}ID{/ts}</th>
                <th>{ts}Name{/ts}</th>
                <th>{ts}Description{/ts}</th>
                <th>{ts}Last modified{/ts}</th>
                <th>{ts}Configuration{/ts}</th>
                <th></th>
            </tr>
        </thead>

        <tbody>
            {foreach from=$templates item=template}
                <tr
                    class="template-row crm-entity"
                    data-action="create"
                    data-entity="SqltaskTemplate"
                    data-id="{$template.id}"
                >
                    <td>
                        {if $defaultTemplateId == $template.id}
                            <i class="crm-i fa-check-circle"></i>
                        {/if}
                    </td>

                    <td>{$template.id}</td>
                    <td class="crm-editable" data-field="name">{$template.name}</td>
                    <td class="crm-editable" data-field="description">{$template.description}</td>
                    <td>{$template.last_modified}</td>
                    <td class="crm-editable" data-field="config">{$template.config}</td>

                    <td>
                        <button
                            class="crm-button download"
                            data-template-id="{$template.id}"
                        >
                            <i class="crm-i fa-download"></i>
                            <span>{ts}Download{/ts}</span>
                        </button>

                        <button
                            class="crm-button set-default"
                            data-template-id="{$template.id}"
                            {if $defaultTemplateId == $template.id}disabled{/if}
                        >
                            <i class="crm-i fa-check-circle"></i>
                            <span>{ts}Set as default{/ts}</span>
                        </button>

                        <button class="crm-button delete" data-template-id="{$template.id}">
                            <i class="crm-i fa-trash"></i>
                            <span>{ts}Delete{/ts}</span>
                        </button>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    <div class="crm-form-block">
        <button class="crm-button" id="open-form">
            <i class="crm-i fa-plus"></i>
            <span>{ts}New Template{/ts}</span>
        </button>

        <form id="new-template" class="hidden">
            <div class="form-field">
                <label for="name">{ts}Name{/ts}</label>
                <input id="name" type="text" class="crm-form-text"/>
            </div>

            <div class="form-field">
                <label for="description">{ts}Description{/ts}</label>
                <input id="description" type="text" class="crm-form-text"/>
            </div>

            <div class="form-field">
                <label for="config">{ts}Configuration{/ts}</label>
                <input id="config" type="file" class="crm-form-file" />
            </div>

            <div class="form-controls">
                <button id="submit-new-template" class="crm-button" type="submit">
                    {ts}Submit{/ts}
                </button>

                <button id="clear-form" class="crm-button" type="button">
                    {ts}Clear{/ts}
                </button>

                <button id="cancel" class="crm-button" type="button">
                    <span>{ts}Cancel{/ts}</span>
                </button>
            </div>
        </form>
    </div>
</div>

{literal}
<script>
    CRM.$($ => {
        const rootNode = $("div#sqltasks-templates");
        const openFormButton = rootNode.find("button#open-form");
        const closeFormButton = rootNode.find("button#close-form");
        const newTemplateForm = rootNode.find("form#new-template");

        const inputElements = {
            name: newTemplateForm.find("input#name"),
            description: newTemplateForm.find("input#description"),
            config: newTemplateForm.find("input#config"),
        }

        const submitButton = newTemplateForm.find("button#submit-new-template");
        const clearButton = newTemplateForm.find("button#clear-form");
        const cancelButton = newTemplateForm.find("button#cancel");

        init();

        function clearForm () {
            Object.values(inputElements).forEach(elem => elem.val(""));
            onInput();
        }

        function closeNewTemplateForm () {
            clearForm();
            newTemplateForm.addClass("hidden");
            openFormButton.removeClass("hidden");
        }

        function deleteTemplate (templateId) {
            return () => {
                CRM.confirm({
                    title: ts("Confirm deletion"),
                    message: ts("Are you sure you want to delete this template?"),
                    options: { yes: ts("Yes"), no: ts("No") },
                }).on("crmConfirm:yes", () => {
                    CRM.api3("SqltaskTemplate", "delete", {
                        "id": templateId,
                    }).then(result => {
                        if (result.is_error) {
                            handleAPIError("Deletion of template failed", result.error_message);
                        } else {
                            window.location.reload();
                        }
                    });
                }).on("crmConfirm:no", () => { /* Do nothing */ });
            };
        }

        function downloadTemplateConfig (templateId) {
            return () => {
                const config = rootNode.find(`tr[data-id="${templateId}"] td[data-field="config"]`).text();

                const downloadLink = document.createElement("a");
                downloadLink.classList.add("hidden");
                downloadLink.setAttribute("href", `data:text/plain;charset=utf-8,${encodeURIComponent(config)}`);
                downloadLink.setAttribute("download", `sqltask-template-${templateId}.json`);

                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            };
        }

        function getInputValues () {
            return Object.entries(inputElements).reduce(
                (result, [key, elem]) => Object.assign(result, { [key]: elem.val() }),
                {}
            );
        }

        function init () {
            rootNode.find("tr.template-row button.download").each((_, button) => {
                const templateId = button.getAttribute("data-template-id");
                button.addEventListener("click", downloadTemplateConfig(templateId));
            });

            rootNode.find("tr.template-row button.set-default").each((_, button) => {
                const templateId = button.getAttribute("data-template-id");
                button.addEventListener("click", setDefaultTemplate(templateId));
            });

            rootNode.find("tr.template-row button.delete").each((_, button) => {
                const templateId = button.getAttribute("data-template-id");
                button.addEventListener("click", deleteTemplate(templateId));
            });

            openFormButton.click(openNewTemplateForm);

            inputElements.name.on("input", onInput);
            inputElements.description.on("input", onInput);
            inputElements.config.on("input", onInput);

            newTemplateForm.submit(submitNewTemplate);
            clearButton.click(clearForm);
            cancelButton.click(closeNewTemplateForm);
        };

        function handleAPIError (alertMsg, logMsg) {
            console.error(logMsg);
            CRM.alert(ts(alertMsg), ts("Error"), "error");
        }

        function onInput () {
            const { name, config } = getInputValues();

            if (!name || !config) {
                submitButton.attr("disabled", "disabled");
            } else {
                submitButton.removeAttr("disabled");
            }
        }

        function openNewTemplateForm () {
            openFormButton.addClass("hidden");
            newTemplateForm.removeClass("hidden");
            onInput();
        };

        function setDefaultTemplate (templateId) {
            return () => {
                CRM.api3("Setting", "create", {
                    "sqltasks_default_template": templateId,
                }).then(result => {
                    if (result.is_error) {
                        handleAPIError("Setting of default template failed", result.error_message);
                    } else {
                        window.location.reload();
                    }
                });
            };
        }

        function submitNewTemplate (event) {
            event.preventDefault();
            event.stopPropagation();

            const inputValues = getInputValues();

            const file = inputElements.config.get(0).files[0];
            const fileReader = new FileReader();

            fileReader.onload = () => {
                inputValues.config = fileReader.result;
                clearForm();

                CRM.api3("SqltaskTemplate", "create", inputValues).then(result => {
                    if (result.is_error) {
                        handleAPIError("Creation of new template failed", result.error_message);
                    } else {
                        window.location.reload();
                    }
                });
            };

            fileReader.readAsText(file);
        }
    });
</script>

<style>
    div#sqltasks-templates > div.crm-form-block {
        padding: 20px;
    }

    div#sqltasks-templates *.hidden {
        display: none;
    }

    div#sqltasks-templates td:nth-child(1) {
        color: #0071bd;
    }

    div#sqltasks-templates td:nth-child(2) { min-width: 30px }
    div#sqltasks-templates td:nth-child(3) { min-width: 100px }
    div#sqltasks-templates td:nth-child(4) { min-width: 200px }
    div#sqltasks-templates td:nth-child(5) { min-width: 140px }
    div#sqltasks-templates td:nth-child(7) { min-width: 400px }

    div#sqltasks-templates td:last-child {
        border:none;
    }

    div#sqltasks-templates form#new-template {
        max-width: 800px;
    }

    div#sqltasks-templates form#new-template div.form-field,
    div#sqltasks-templates form#new-template div.form-controls {
        margin-top: 10px;
    }

    div#sqltasks-templates form#new-template div.form-field {
        display: flex;
        flex-direction: column;
    }
</style>
{/literal}
