# Usage

Tasks can be managed by going to *Administer* → *Automation* →
*SQL Tasks*. The task manager shows a list of all tasks and supports
actions like changing the task configuration or running them. You can also
change the order in which they are executed when started via the dispatcher.
This can be done by either clicking the arrows under "Selection Order" or by
using drag-and-drop on the rightmost icon.

## Options

There are a number of options that can be set for each task:

- **Name**, **Description** and **Category** can be used to document and
  organize tasks
- **Execution** defines the task execution schedule (see [Scheduling SQL Tasks](#scheduling-sql-tasks))
- **Allow parallel execution** lets you decide whether a task should run in parallel:
    - **No**: The task will not run if any other tasks are already running.
    - **With other running tasks**: The task will be executed even if other tasks
      are still running, but not if the task itself is already running.
    - **Always (multiple instances)**: The task will always be executed,
      potentially causing multiple instances of the task to run in parallel.
- **Run Permissions** can be used to make certain tasks available for execution
  by users without admin permissions from the *Contacts* → *My Tasks* menu.
  These users will only be able to execute tasks, but cannot modify tasks.
- **Require user input** allows users or other integrations to provide input
  parameters to tasks. Refer to the integration chapters for more details.
- **Abort on error** defines whether tasks should stop executing when an error
  is encountered. "Error Handler" and "Run Cleanup SQL Script" actions will be
  executed regardless of this setting.

## Actions

Tasks are SQL-driven, meaning the workflow usually starts with an SQL query
followed by a number of actions that work with the query results. Each action
accepts a certain number of inputs and performs work based on them, e.g. running
an SQL queries or performing API calls.

Actions can be ordered arbitrarily (using drag and drop), and any number of
actions can be added to a task.

SQL Tasks currently supports these actions:

### Create Activity

This can be used to create activities for the selected contacts. It is possible
to create individual (one per contact) or mass activities (one activity assigned
to many contacts). Some activity fields support tokens, which are useful
when adding information from your data table to fields like the subject. Note
that hooks are not triggered unless you use the "Use API" option (which performs
significantly worse).

!!! tip
    If you want to create activities using parameters that are not supported by
    this action, you can use the [API Call action](#api-call) instead.

### API Call

This action performs an API call for every row in your data table. You can use
any of the entities and actions supported by your installation. The API
parameters can be set either statically or using tokens that represent columns
in your data table.

#### API Error Handling

API calls may encounter an error during execution. The "API Call" action
supports multiple error handling strategies for these scenarios:

- **Log only** (default)
  Log API errors and continue. Don't trigger Error Handler action.
- **Report task error and continue API calls**
  Continue with remaining API calls (i.e. rows in the data table that have not
  been processed yet) and then report task error to Error Handler.
- **Report task error and abort API calls**
  Abort remaining API calls and report task error to Error Handler.

### CSV Export

The CSV Export action is useful when you want to create custom CSV files based
on the table created by your SQL script. You can configure the file format, path
and name and determine how the file should be delivered. CSV Export currently
supports SFTP and email delivery.

!!! tip
    If you need to share files using something not natively supported by the
    CSV Export action, you may want to investigate [rclone](https://rclone.org). `rclone`
    allows you to mount various types of file sharing tools as a file system
    on your server, so you can access it like any other directory and use it
    as a file path in your action. Note that this requires admin-level access
    to your server.

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

### Run PHP Code

The Run PHP Code action allows execution of arbitrary PHP code within a task.
This action type should be used sparingly and only if the alternative is
way more complex. For most use-cases, custom PHP code should only be used
within CiviCRM extension.

Code is executed within the context of an instance of `CRM_Sqltasks_Action_RunPHP`.
It is therefore possible to invoke the task logger and/or error handler
from within the custom code as follows:

```php
// Example: add a message to the task execution log:
$this->log('Custom PHP code executed successfully');

// Example: mark the task as failed
$this->task->setErrorStatus();
```

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
frequency you run the `Job.execute` CiviCRM cronjob at. For each frequency, you
can configure the exact time and date of execution. Note that in addition to
setting the task schedule, you also need to enable the task on the task list.

See [Configuration](configuration.md) for details on how to enable automatic
execution.

!!! warning
    When enabling a task for the first time (or when re-enabling it after
    it has skipped an execution window), the task will always be executed
    immediately whenever the Scheduled Job for SQL Tasks is executed.

    This can be counter-intuitive e.g. for a daily task scheduled to run
    at 18:00 when task is enabled at 14:20; in this scenario, if the Scheduled
    Job (and the corresponding cronjob) is set to run every hour, the task will
    be executed at 15:00.

## Exporting SQL Tasks

Tasks can be exported as `.sqltask` files for easy copying to other
installations and to facilitate sharing of tasks. A
[repository of example tasks](https://github.com/systopia/de.systopia.sqltasks/tree/master/tasks/readme.md)
is available as part of this project.

!!! warning
    When importing tasks from other systems, it is recommended to check all
    environment-specific values used in actions.

    For example, when sharing a task that includes a "Create Activity" action,
    the task may refer to a CiviCampaign by its ID. It is not guaranteed that
    the campaign exists and refers to the same campaign on a different system.

## Best practices

It can be quite easy to accumulate a large number of long-running tasks
over time, so it's generally advisable to use a number of best practices
to reduce execution time when developing new tasks.

Each SQL task should only query those records necessary for the action to take,
in order to avoid unneeded write queries to the database.

Also, schedule your tasks with reasonable intervals. Not every task needs to be
run each time the Job Scheduler is executing.

Instead of making `INSERT`, `UPDATE`, or `DELETE` queries directly within the
task's SQL code, always prefer using the "API Call" action, or any other
native SQL Task action for the entity you're working with (e.g.
"Synchronize Tag" for tags), as this helps to preserve data integrity and allows
other things to happen for each action (e.g. business logic implemented in
CiviCRM core, logging, or running hook implementations by other extensions).

For queries that don't need additional indices, prefer to create views instead
of tables, as this increases performance.
