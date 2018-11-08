# Usage

Tasks can be managed by going to *Administer* → *System Settings* →
*Manage SQL Tasks*. The task manager shows a list of all tasks and supports
actions like changing the task configuration or running them. You can also
change the order in which they are executed when started via the dispatcher.
This can be done by either clicking the arrows under "Selection Order" or by
using drag-and-drop on the rightmost icon.

## Best practices

When developing SQL tasks, you should check the cron configuration for your web
server, since SQL tasks may take a long time to execute, depending on what they
do, and there could be interferences with other cron jobs interacting with the
database, such as regular dumps. Make sure that each cron job gets the chance to
finish before another one is accessing the database.

Therefore, each SQL task should only query those records necessary for the
action to take, in order to avoid unneeded write queries to the database.

Also, schedule your tasks with reasonable intervals. Not every task needs to be
run each time the Job Scheduler is executing.

Instead of making `INSERT`, `UPDATE`, or `DELETE` queries directly within the
task's SQL code, always prefer using the "API Call" action, as this helps
preserving data integrity and allows other things to happen for each action
(e.g. logging, or running any hook implementations by other extensions).

For queries that don't need additional indices, prefer to create views instead
of tables, as this increases performance.

## Actions

Tasks are SQL-driven, meaning the workflow usually starts with an SQL query
followed by a number of actions that work with the query results.

SQL Tasks currently supports these actions:

### Create Activity

This can be used to create activities for the selected contacts. It is possible
to create individual (one per contact) or mass activities (one activity assigned
to many contacts). Some of the activity fields support tokens, which are useful
when adding information from your data table to fields like the subject. Note
that hooks are not triggered unless you use the "Use API" option (which performs
significantly worse).

### API Call

This action performs an API call for every row in your data table. You can use
any of the entities and actions supported by your installation. The API
parameters can be set either statically or using tokens that represent columns
in your data table.

### CSV Export

The CSV Export action is useful when you want to create custom CSV files based
on the table created by your SQL script. You can configure the file format, path
and name and determine how the file should be delivered. CSV Export currently
supports SFTP and email delivery.

### Synchronise Tag

The Synchronise Tag action allows you to synchronize all the contacts in your
data table with your contact's tags. All contacts included in your data table
will be assigned to the tag you select, while it will be removed for all others.
The synchronization is implemented using raw SQL, so it performs well in large
environments with tags assigned to hundreds of thousands of contacts. Note that
hooks are not triggered unless you use the "Use API" option (which will take
significantly longer). 

### Synchronise Group

Works the same way as "Synchronise Tags", only for groups.

### Success Handler

The Success Handler is executed when a job runs successfully, without any
errors. You can use it to get email notifications after execution.

### Error Handler

Similar to the Success Handler, the Error Handler is only executed when an error
occurs during execution. It is highly recommended to set up an error handler for
all your recurring tasks as you might not notice breakage otherwise.

## Scheduling SQL Tasks

Tasks can either be executed manually or based on a schedule. You can run tasks
hourly, daily, weekly, monthly or annual, or, with "always", using whatever
frequency you run the `Job.execute` CiviCRM cronjob at.

SQL Tasks comes with a "Run SQL Tasks" Scheduled Job that is disabled by
default. To enable automatic execution of tasks, enable this job.

## Exporting SQL Tasks

Tasks can be exported as `.sqltask` files for easy copying to other
installations and to facilitate sharing of tasks. A
[repository of example tasks](https://github.com/systopia/de.systopia.sqltasks/tree/master/tasks/readme.md) is available as part of this
project.
