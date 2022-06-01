# Configuraton

## Scheduled Job

By default, the extension is installed with a disabled Scheduled Job
called "Run SQL Tasks". To enable automatic execution of your tasks,
this job should be enabled. The run frequency of the Scheduled Job
is the minimum frequency at which tasks can be executed, but individual
tasks can set their own schedule (see the next chapter). It is recommended
to set the frequency to "always" to allow individual tasks to use the
schedule that best fits its needs.

You should check the cron configuration for your web server, since SQL tasks
may take a long time to execute, depending on what they do. SQL Tasks could
interfere with other cron jobs interacting with the database, such as regular
dumps. Make sure that each cron job gets the chance to finish before another
one is accessing the database.
