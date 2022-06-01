# Global Tokens

Global Tokens can be used as a centralized place to store values
that are reused across multiple tasks. This is particularly useful
for things like credentials or API keys, which you may not want to
include in your task exports if you store them outside of CiviCRM
(e.g. in git).

Global Tokens are effectively a key-value store. To access them,
go to *Administer* → *Automation* → *SQL Tasks* → *Global Token Manager*.
The Global Token Manager page allows you to create new tokens and change
values of existing tokens.

To reference the value of a token within a task, use the `{config.name_of_token}`
syntax. Global Tokens can be used within all action fields in a task.

As an example, you may want to upload files to an SFTP server using the
"CSV Export" action. Instead of storing the password in the task itself,
you can define a global token called `sftp_password` and reference it in
the "Upload to" field of the "CSV Export" action as follows:

    sftp://user:{config.sftp_password}@host/path

When the task is executed, `{config.sftp_password}` will automatically be
replaced with the value of the token (i.e. the password).

!!! tip
    Global Tokens are quite useful if you're trying to maintain a staging
    environment that's using production data while trying to avoid side-effects
    of accidental SQL Task executions. For example, as part of your process
    for creating the staging environment, you could remove any production
    credentials, export paths or emails (or replace them with test values).
    This would allow you to execute tasks on staging environments without
    accidentally exporting files to a partner or emailing your clients.
