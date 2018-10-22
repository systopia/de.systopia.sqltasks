# Security Considerations

SQLTasks is only available to users with the "administer CiviCRM" permission;
you should ensure that all users with this permission know what they're doing
and are fully trusted.

The SQLTasks extension allows execution of arbitrary SQL statements. Users with
the "administer CiviCRM" permission can perform actions on **all databases**
accessible by the database user used by Civi. Make sure to use per-database
users in multi-tenant environments to avoid exposing data of other
organizations.

It is highly recommended that you use a test installation or **create a backup**
of the entire database before experimenting with tasks.
