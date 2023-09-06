<div class="global-token__page">
  <div class="crm-block crm-form-block page-civicrm-admin">
    <div class="crm-content-block crm-block">

      <div class="help">
        <p>{ts}You can manage global tokens below. Global tokens can be used to store values that are used in multiple SQL Tasks, allowing for the value to be changed everywhere by just editing the token. This can be useful for things like credentials.{/ts}</p>
        <p>{ts}To use a global token, use this syntax in any task field:{/ts}</p>
        <p><code>{ts}{literal}{config.name_of_token}{/literal}{/ts}</code></p>
      </div>

      <div class="global-token__table-wrap">
        <table class="global-token__table form-layout">
          <thead>
            <tr>
              <th class="global-token__large-column">
                  {ts}Name{/ts}
              </th>
              <th class="global-token__large-column">
                  {ts}Value{/ts}
              </th>
              <th>
                  {ts}Actions{/ts}
              </th>
            </tr>
          </thead>
          <tr class="global-token__add-new-token-row">
            <td>
              <input class="global-token__create-token-name-input crm-form-text" type="text" maxlength="{$maxLengthOfTokenName}">
              <div class="global-token__create-token-error-message-wrap"></div>
            </td>
            <td>
              <input class="global-token__create-token-value-input crm-form-text" type="text">
            </td>
            <td>
              <div>
                <a class="button global-token__button" id="globalTokenCreateButton" href="javascript:;">
                  <span><i class="crm-i fa-plus-circle"></i><span>{ts}Create new global token{/ts}</span></span>
                </a>
              </div>
            </td>
          </tr>
        </table>
      </div>

    </div>
  </div>

  {*this table is hidden and the row will be used like a template*}
  <table class="global-token__table-template">
    <tr class="global-token__row-template">
      <td>
        <div class="global-token__edit-mode">
          <input class="global-token__edit-token-name-input crm-form-text" maxlength="{$maxLengthOfTokenName}" type="text">
          <div class="global-token__edit-token-error-message-wrap"></div>
        </div>
        <div class="global-token__view-mode">
          <div class="global-token__token-name"></div>
        </div>
      </td>
      <td>
        <div class="global-token__edit-mode">
          <input class="global-token__edit-token-value-input crm-form-text" type="text">
        </div>
        <div class="global-token__view-mode">
          <div class="global-token__token-value">
          </div>
        </div>
      </td>
      <td>
        <div class="global-token__edit-mode">
          <a class="button global-token__button global-token__update-token-data-button" href="javascript:;">
            <span><i class="crm-i fa-check"></i><span>{ts}Update{/ts}</span></span>
          </a>
          <a class="button global-token__button global-token__cancel-editing-token-data-button" href="javascript:;">
            <span><i class="crm-i fa-close"></i><span>{ts}Cancel{/ts}</span></span>
          </a>
        </div>
        <div class="global-token__view-mode">
          <a class="button global-token__button global-token__edit-token-data-button" href="javascript:;">
            <span><i class="crm-i fa-pencil"></i><span>{ts}Edit{/ts}</span></span>
          </a>
          <a class="button global-token__button global-token__delete-token-button" href="javascript:;">
            <span><i class="crm-i fa-trash"></i><span>{ts}Delete{/ts}</span></span>
          </a>
        </div>
      </td>
    </tr>
  </table>

</div>

{literal}
<script>
    CRM.$(function ($) {
        var tokens = {/literal}{$tokens}{literal};
        var templateColumns = $('.global-token__table-template .global-token__row-template').html();
        var table =  $('.global-token__table');
        var createTokenErrorMessageElement =  $('.global-token__create-token-error-message-wrap');

        addTokensToTable();
        initCreatingNewToken();

        function addTokensToTable() {
            for (var i = 0; i < tokens.length; i++) {
                addTokenToTable(tokens[i]);
            }
        }

        function addTokenToTable(token) {
            table.find('.global-token__add-new-token-row').before('<tr class="global-token__row" data-token-name="' + token.name + '"></td>');
            var tokenRow = $("tr.global-token__row[data-token-name='" + token.name + "']");
            tokenRow.append(templateColumns);

            tokenRow.find('.global-token__token-name').text(token.name);
            tokenRow.find('.global-token__token-value').text(token.value);

            tokenRow.find('.global-token__edit-token-data-button').click(function () {
                tokenRow.addClass('edit-mode');
                tokenRow.find('.global-token__edit-token-name-input').val(tokenRow.find('.global-token__token-name').text());
                tokenRow.find('.global-token__edit-token-value-input').val(tokenRow.find('.global-token__token-value').text());
            });

            tokenRow.find('.global-token__delete-token-button').click(function () {
                showDeleteTokenWindow(tokenRow);
            });

            tokenRow.find('.global-token__update-token-data-button').click(function () {
                updateTokenData(tokenRow);
            });

            tokenRow.find('.global-token__cancel-editing-token-data-button').click(function () {
                tokenRow.removeClass('edit-mode');
            });

            return tokenRow;
        }

        function updateTokenData(tokenRow) {
            var newName = tokenRow.find('.global-token__edit-token-name-input').val();
            var value = tokenRow.find('.global-token__edit-token-value-input').val();
            var name = tokenRow.data('token-name');
            var errorMessageElement = tokenRow.find('.global-token__edit-token-error-message-wrap');
            errorMessageElement.empty();

            if (newName.length < 1) {
                errorMessageElement.append(getErrorMessageHtml(ts("Name cannot be empty.")));
                return;
            }

            CRM.api3('SqltaskGlobalToken', 'update_token', {
                "new_name": newName,
                "value": value,
                "name": name
            }).done(function(result) {
                if (result.is_error === 0) {
                    tokenRow.removeClass('edit-mode');
                    tokenRow.find('.global-token__token-name').text(newName);
                    tokenRow.find('.global-token__token-value').text(value);
                    tokenRow.data('token-name', newName);
                    tokenRow.effect('highlight', {}, 1500);
                    CRM.alert(ts('Global token successfully updated!'), ts("Updating global token"), "success");
                } else {
                    errorMessageElement.append(getErrorMessageHtml(result.error_message));
                }
            });
        }

        function showDeleteTokenWindow(tokenRow) {
            CRM.confirm({
                title: ts('Remove global token'),
                message: ts('Are you sure?'),
            }).on('crmConfirm:yes', function() {
                var tokenName = tokenRow.data('token-name');
                CRM.api3('SqltaskGlobalToken', 'delete_token', {"name": tokenName}).done(function(result) {
                    if (result.is_error === 0) {
                        CRM.alert(ts('Global token successfully deleted!'), ts("Deleting global token"), "success");
                        tokenRow.remove();
                    } else {
                        CRM.alert(result.error_message, ts("Error deleting global token"), "error");
                    }
                });
            });
        }

        function initCreatingNewToken() {
            $('#globalTokenCreateButton').click(function () {
                var row = $(this).closest('.global-token__add-new-token-row');
                var nameElement = row.find('.global-token__create-token-name-input');
                var valueElement = row.find('.global-token__create-token-value-input');
                var name = nameElement.val();
                var value = valueElement.val();
                createTokenErrorMessageElement.empty();

                if (name.length < 1) {
                    createTokenErrorMessageElement.append(getErrorMessageHtml(ts("Name cannot be empty.")));
                    return;
                }

                CRM.api3('SqltaskGlobalToken', 'create', {
                    "name": name,
                    "value": value
                }).done(function(result) {
                    if (result.is_error === 0) {
                        var tokenRow = addTokenToTable({'name' : name, 'value' : value});
                        tokenRow.effect('highlight', {}, 1500);
                        valueElement.val('');
                        nameElement.val('');
                        CRM.alert(ts('Global token successfully created!'), ts("Creating global token"), "success");
                    } else {
                        createTokenErrorMessageElement.append(getErrorMessageHtml(result.error_message));
                    }
                });
            });
        }

        function getErrorMessageHtml(message) {
            return '<span class="crm-error">' + message + '</span>';
        }

    });
</script>

<style>
  .global-token__edit-mode {
    display: none;
  }

  .global-token__row.edit-mode .global-token__edit-mode {
    display: block;
  }

  .global-token__row.edit-mode .global-token__view-mode {
    display: none;
  }

  .global-token__table-template {
    display: none !important;
  }

  .global-token__page .global-token__button span span {
    display: inline-block;
    vertical-align: middle;
  }

  .global-token__page .global-token__button span i {
    display: inline-block;
    vertical-align: middle;
    margin: 0 5px 0 0;
  }

  .global-token__page a:hover .crm-i.fa-trash {
    color: inherit;
  }

  .global-token__button {
    white-space: nowrap;
  }

  .global-token__large-column {
    width: 50%;
  }

  .global-token__token-value {
    max-width: 600px;
  }

  .global-token__token-name {
    max-width: 500px;
  }

  .global-token__page .global-token__add-new-token-row .global-token__create-token-value-input,
  .global-token__page .global-token__add-new-token-row .global-token__create-token-name-input ,
  .global-token__page .global-token__row .global-token__edit-token-name-input ,
  .global-token__page .global-token__row .global-token__edit-token-value-input {
    width: 100%;
    max-width: 100%;
    display: block;
    box-sizing: border-box;
    height: 30px;
  }
</style>
{/literal}
