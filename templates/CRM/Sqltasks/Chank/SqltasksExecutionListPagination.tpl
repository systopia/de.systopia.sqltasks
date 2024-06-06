{if $pagination}
  <div class="sql-task__execution-list-pagination-wrap">
    <div>
      <span>Executions {$pagination.show_from_count} - {$pagination.show_to_count} of {$pagination.all_count}</span>
    </div>
    <div>
      <span>Page {$pagination.current_page_number}</span>
    </div>
      {if $pagination.first_link}<a href="{$pagination.first_link}">&#60;&#60; First</a>{/if}
      {if $pagination.prev_link}<a href="{$pagination.prev_link}">&#60; Prev</a>{/if}
      {if $pagination.next_link}<a href="{$pagination.next_link}">Next&#62;</a>{/if}
      {if $pagination.last_link}<a href="{$pagination.last_link}">Last&#62;&#62;</a>{/if}
  </div>
{/if}

{literal}
<style>

.sql-task__execution-list-pagination-wrap {
  padding-bottom: 10px;
  display: flex;
  gap: 20px;
}

</style>
{/literal}
