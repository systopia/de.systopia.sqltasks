# SQL Tasks Extension - Configurable recurring tasks

[![Run unit tests](https://github.com/systopia/de.systopia.sqltasks/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/systopia/de.systopia.sqltasks/actions/workflows/unit-tests.yml)


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
- API4 is [supported in version 2.x+](https://github.com/systopia/de.systopia.sqltasks/tags)
- Export to CSV, including zipping, uploading, emailing the results
- Synchronize a SQL result contact ID list with a given tag or group

Remark: Synchronisation is particularly useful to replace complex dynamic groups
(which often cause performance issues) with a static group that is updated
hourly/nightly/weekly.

The extension also features a simple ex- and import of your task, and we plan to
create a little repository of common tasks, see
[here](https://github.com/systopia/de.systopia.sqltasks/tree/master/tasks/readme.md).

Also be aware that this extension will run any SQL script you enter in your
task - there is no filters! It is quite easy to break your system if you don't
know what you're doing.

## Documentation
Read the full documenation [here](https://docs.civicrm.org/sqltasks/en/latest/).

## We need your support
This CiviCRM extension is provided as Free and Open Source Software, and we are happy if you find it useful. However, we have put a lot of work into it (and continue to do so), much of it unpaid for. So if you benefit from our software, please consider making a financial contribution so we can continue to maintain and develop it further.

If you are willing to support us in developing this CiviCRM extension, please send an email to info@systopia.de to get an invoice or agree a different payment method. Thank you! 

## Credits
Greenpeace Central And Eastern Europe did a huge job developing most of the functionality that was merged into version 2.0. Thank you!
