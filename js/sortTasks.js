cj( document ).ready(function() {

    var sortable = cj( "#sortable-tasks" ).sortable({
        placeholder: "ui-state-highlight",
        handle: '.handle',
        forceHelperSize: true,
        items: '.sorting-init',
        update: function (event, ui) {
            sortable.find('tr').addClass('sorting-init');
            sortable.sortable('refresh');
            var data = cj(this).sortable( "toArray");

            CRM.api3('Sqltask', 'sort', {
                "sequential": 1,
                "data": data
            }).done(function(result) {
                /*
                Do something when sorting is finished via api. (for debugging purposes.)
                 */
            });

        }
    });
    sortable.find('tr').one('mouseenter', function() {
        cj(this).addClass('sorting-init');
        sortable.sortable('refresh');
    });
});
