cj( document ).ready(function() {
    taskScreenOrder = fetchScreenOrder();

    var sortable = cj( "#sortable-tasks" ).sortable({
        placeholder: "ui-state-highlight",
        handle: '.handle',
        forceHelperSize: true,
        items: '.sorting-init',
        update: function (event, ui) {
            sortable.find('tr').addClass('sorting-init');
            sortable.sortable('refresh');
            var taskOrder = cj(this).sortable( "toArray");

            CRM.api3('Sqltask', 'sort', {
                "sequential": 1,
                "data": taskOrder,
                "task_screen_order": taskScreenOrder
                }).done(function(result) {
                    if(result.is_error == 1){
                        alert('The following error occured: ' + result.error_message);
                        window.location.reload();
                    }
                    taskScreenOrder = fetchScreenOrder();
                }).fail(function(result) {
                    console.log('fail: ' + result);
                });
        }
    });

    sortable.find('tr').one('mouseenter', function() {
        cj(this).addClass('sorting-init');
        sortable.sortable('refresh');
    });

    function fetchScreenOrder(){
        // fetch the taskorder from the screen before it is changed
        var taskScreenOrder = [];
        
        cj('tr').each(function(){
            var id = cj(this).attr('id');
            // console.log('id' + id);
            if(id) taskScreenOrder.push(id);
        });

        return taskScreenOrder;
    }
});
