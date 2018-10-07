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
    <div class="label">{$form.segmentation_export_campaign_id.label}</div>
    <div class="content">{$form.segmentation_export_campaign_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_export_segments.label}</div>
    <div class="content">{$form.segmentation_export_segments.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section export-date">
    <div class="label">{$form.segmentation_export_date_from.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Timestamp Options{/ts}", {literal}{"id":"id-segmentation-export-datetime","file":"CRM\/Sqltasks\/Action\/SegmentationExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <!--div class="content">{include file="CRM/common/jcalendar.tpl" elementName=segmentation_export_date_from}</div-->
    <div class="content">{$form.segmentation_export_date_from.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section export-date">
    <div class="label">{$form.segmentation_export_date_to.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Timestamp Options{/ts}", {literal}{"id":"id-segmentation-export-datetime","file":"CRM\/Sqltasks\/Action\/SegmentationExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <!--div class="content">{include file="CRM/common/jcalendar.tpl" elementName=segmentation_export_date_to}</div-->
    <div class="content">{$form.segmentation_export_date_to.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_export_date_current.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Timestamp Options{/ts}", {literal}{"id":"id-segmentation-export-current","file":"CRM\/Sqltasks\/Action\/SegmentationExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.segmentation_export_date_current.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_export_discard_empty.label}</div>
    <div class="content">{$form.segmentation_export_discard_empty.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_export_exporter.label}</div>
    <div class="content">{$form.segmentation_export_exporter.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_export_filename.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Filename{/ts}", {literal}{"id":"id-segmentation-export-filename","file":"CRM\/Sqltasks\/Action\/SegmentationExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.segmentation_export_filename.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_export_path.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Path{/ts}", {literal}{"id":"id-segmentation-export-path","file":"CRM\/Sqltasks\/Action\/SegmentationExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.segmentation_export_path.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_export_email.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Email{/ts}", {literal}{"id":"id-segmentation-export-email","file":"CRM\/Sqltasks\/Action\/SegmentationExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.segmentation_export_email.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_export_email_template.label}</div>
    <div class="content">{$form.segmentation_export_email_template.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_export_upload.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}SFTP Upload{/ts}", {literal}{"id":"id-segmentation-export-sftp","file":"CRM\/Sqltasks\/Action\/SegmentationExport"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.segmentation_export_upload.html}</div>
    <div class="clear"></div>
  </div>
</div>


<script type="text/javascript">
{literal}
// this script disables the date_from date_to fields if the date_current checkbox is ticked.
function segmentation_export_updateDateStatus() {
  var current_assignment = cj("input[name=segmentation_export_date_current]").prop('checked');
  cj("div.export-date").find("input").enable(!current_assignment);
}
cj("input[name^=segmentation_export_date_]").change(segmentation_export_updateDateStatus);
segmentation_export_updateDateStatus();
{/literal}
</script>



<script type="text/javascript">

var segmentation_export_segments_current = {$segmentation_export_segments_current};

{literal}

/*******************************
 *   campaign changed handler  *
 ******************************/
cj("#segmentation_export_campaign_id").change(function() {
  // rebuild segment list
  cj("#segmentation_export_segments option").remove();
  CRM.api3('Segmentation', 'segmentlist', {
    "campaign_id": cj("#segmentation_export_campaign_id").val(),
  }).done(function(result) {
    for (segment_id in result.values) {
      cj("#segmentation_export_segments").append('<option value="' + segment_id + '">' + result.values[segment_id] + '</option>');
    }
    if (segmentation_export_segments_current == 'CHANGED') {
      cj("#segmentation_export_segments").val('');
    } else {
      cj("#segmentation_export_segments").val(segmentation_export_segments_current);
      segmentation_export_segments_current = 'CHANGED';
    }
    cj("#segmentation_export_segments").change();
  });
});

// fire off event once
cj("#segmentation_export_campaign_id").change();
{/literal}
</script>