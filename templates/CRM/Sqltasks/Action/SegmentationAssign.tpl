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
    <div class="label">{$form.segmentation_assign_table.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Data table{/ts}", {literal}{"id":"id-segmentation-assign-table","file":"CRM\/Sqltasks\/Action\/SegmentationAssign"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.segmentation_assign_table.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_assign_campaign_id.label}</div>
    <div class="content">{$form.segmentation_assign_campaign_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section assign-segment">
    <div class="label">{$form.segmentation_assign_segment_name.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Segment{/ts}", {literal}{"id":"id-segmentation-assign-segment-name","file":"CRM\/Sqltasks\/Action\/SegmentationAssign"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.segmentation_assign_segment_name.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.segmentation_assign_segment_from_table.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Segment from data table{/ts}", {literal}{"id":"id-segmentation-assign-segment-from-table","file":"CRM\/Sqltasks\/Action\/SegmentationAssign"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.segmentation_assign_segment_from_table.html}</div>
    <div class="clear"></div>
  </div>
</div>

<script type="text/javascript">
{literal}
// this script disables the date_from date_to fields if the date_current checkbox is ticked.
function segmentation_assign_updateSegmentSource() {
  var use_segment_from_table = cj("input[name=segmentation_assign_segment_from_table]").prop('checked');
  cj("div.assign-segment").find("input").enable(!use_segment_from_table);
}
cj("input[name=segmentation_assign_segment_from_table]").change(segmentation_assign_updateSegmentSource);
segmentation_assign_updateSegmentSource();
{/literal}
</script>