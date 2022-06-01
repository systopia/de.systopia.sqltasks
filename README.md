# SQL Tasks Extension - Configurable recurring tasks

[![CircleCI](https://circleci.com/gh/systopia/de.systopia.sqltasks.svg?style=svg)](https://circleci.com/gh/systopia/de.systopia.sqltasks)

*Disclaimer*: The scope of this extension is implementers and skilled
administrators only.

Creating a custom scheduled job is a bit of work, you had to create (i.e. code)
a custom API and then call it via a scheduled job. If you have a lot of those,
it is also quite easy to lose track of them.

The "SQL Tasks" extension allows you simply configure any number of scheduled
jobs via the UI. It is essentially a SQL driven, configurable execution of any
of the following generic CiviCRM actions:

- Create activities
- Call any CiviCRM API3 action
- Export to CSV, including zipping, uploading, emailing the results
- Synchronize a SQL result contact ID list with a given tag or group

Remark: Synchronisation is particularly useful to replace complex dynamic groups
(which often cause performance issues) with a static group that is updated
hourly/nightly/weekly.

The extension also features a simple ex- and import of your task, and we plan to
create a little repository of common tasks, see
[here](https://github.com/systopia/de.systopia.sqltasks/tree/master/tasks/readme.md).

Be aware that this extension is still under development.

Also be aware that this extension will run any SQL script you enter in your
task - there is no filters! It is quite easy to break your system if you don't
know what you're doing.
