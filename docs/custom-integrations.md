# Custom Integrations

SQL Tasks may be configured to accept an input parameter. This allows other
extensions to execute SQL Tasks as part of their inner workings, for
example to allow administrators to implement custom business logic when
certain events are triggered in their extension.

!!! warning
    It is necessary to select the "Require user input" task option to
    enable this feature for a task.

SQL Tasks can be executed using the `Sqltask.execute` API3 endpoint. The
endpoint accepts two parameters that are relevant for this use-case:

- `task_id`: The ID of the task to be executed
- `input_val`: The input parameter that will be provided to the task.
  The value can be an arbitrary data type depending on the use-case;
  you may want to provide a single ID or a complex JSON object for
  later processing within the task. See the documentation for the
  [CiviRules integration](civirules.md) for an example with JSON.

For integrations that want to offer a list of tasks to choose from,
it's possible to get a list of all tasks using the `Sqltask.getalltasks`
endpoint.

Tasks can also be added by other extension (e.g. as part of their
installation process) using the `Sqltask.create` API.
