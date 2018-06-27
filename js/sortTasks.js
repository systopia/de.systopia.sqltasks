cj( document ).ready(function() {

    cj( "#sortable-tasks" ).sortable({
        placeholder: "ui-state-highlight",
        handle: '.handle',
        forceHelperSize: true,
        update: function (event, ui) {

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
    //cj( "#sortable-tasks" ).disableSelection();
});
