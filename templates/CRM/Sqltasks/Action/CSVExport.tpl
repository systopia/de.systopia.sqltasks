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

<div class="sql-tasks">
  <div class="crm-section">
    <div class="label">{$form.csv_table.label}</div>
    <div class="content">{$form.csv_table.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.csv_encoding.label}</div>
    <div class="content">{$form.csv_encoding.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.csv_delimiter.label}</div>
    <div class="content">{$form.csv_delimiter.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section" id="section-delimiter-other">
    <div class="label">{$form.csv_delimiter_other.label}</div>
    <div class="content">{$form.csv_delimiter_other.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.csv_headers.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Columns{/ts}", {literal}{"id":"id-csv-columns","file":"CRM\/Sqltasks\/Action\/CSVExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.csv_headers.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.csv_discard_empty.label}</div>
    <div class="content">{$form.csv_discard_empty.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.csv_zip.label}</div>
    <div class="content">{$form.csv_zip.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.csv_filename.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Filename{/ts}", {literal}{"id":"id-csv-filename","file":"CRM\/Sqltasks\/Action\/CSVExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.csv_filename.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.csv_path.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Path{/ts}", {literal}{"id":"id-csv-path","file":"CRM\/Sqltasks\/Action\/CSVExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.csv_path.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.csv_email.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Email{/ts}", {literal}{"id":"id-csv-email","file":"CRM\/Sqltasks\/Action\/CSVExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.csv_email.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.csv_email_template.label}</div>
    <div class="content">{$form.csv_email_template.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.csv_upload.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}SFTP Upload{/ts}", {literal}{"id":"id-csv-sftp","file":"CRM\/Sqltasks\/Action\/CSVExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.csv_upload.html}</div>
    <div class="clear"></div>
  </div>

</div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    if ($('#csv_delimiter').val() == '') {
      $('#section-delimiter-other').show();
    }
    else {
      $('#section-delimiter-other').hide();
    }

    $('#csv_delimiter').change(function() {
      if ($(this).val() == '') {
        $('#section-delimiter-other').show();
      }
      else {
        $('#section-delimiter-other').hide();
      }
    });
  });
</script>
{/literal}
