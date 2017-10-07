# List of example tasks

## Disclaimer

Please be aware that these tasks can potentially damage your system quite severely. Review every task before execution, and make sure to have a backup.

## How to import

In order to import the tasks you will have to first create a dummy task, and then use the "Import Config" action to load the specific task.

### Opt-out Unsubscribe

Author: @systopia

This task will unsubscribe all contacts with an ``opt_out`` flag from all mailing groups. This is to prevent them from receiving newsletters again should the flag be removed at some point. 

[DOWNLOAD](https://raw.githubusercontent.com/systopia/de.systopia.sqltasks/master/tasks/Opt-Out%20Unsubscribe.sqltask)

### Update Bulk

Author: @pbatroff

Will set ``is_bulkmail`` to all (unique) email addresses per contact.

[DOWNLOAD](https://raw.githubusercontent.com/systopia/de.systopia.sqltasks/master/tasks/Update%20Bulk%20Mails.sqltask)

### CiviBanking Cleanup

Author: @systopia

Periodically deletes the ``test`` contributions that the manual processing in CiviBanking sometimes leaves behind.

[DOWNLOAD](https://raw.githubusercontent.com/systopia/de.systopia.sqltasks/master/tasks/CiviBanking%20Cleanup.sqltask)