# Example

You might want to create a task that assigns a "Major Donor" tag to all contacts
who have contributed more than a certain amount during the last 12 months. The
SQL statement - which is what you would put in the "Main Script (SQL)" field -
might look like this:

```SQL
SET @Contribution_Status_Completed = (
  SELECT
    ov.value
  FROM
    civicrm_option_value ov
  WHERE
    option_group_id = (
      SELECT
        id
      FROM
        civicrm_option_group
      WHERE
        name = 'contribution_status'
    )
    AND ov.label = 'Completed');

DROP TABLE IF EXISTS temp_sqltasks_majordonor;

CREATE TABLE IF NOT EXISTS temp_sqltasks_majordonor AS
  SELECT
    ctrb.contact_id
  FROM
    civicrm_contribution ctrb
  WHERE
    receive_date >= CURDATE() - INTERVAL 1 YEAR
    AND ctrb.contribution_status_id = @Contribution_Status_Completed
  GROUP BY
    ctrb.contact_id
  HAVING
    SUM(ctrb.total_amount) >= 1000;
```

This script creates a new database table with one column containing the
Contact ID of all contacts who are major donors. You can also specify a cleanup
script, which is always executed after the task has executed. It is recommended
that you use this to drop any tables you create in your main script. Your script
might look like this:

```sql
DROP TABLE IF EXISTS temp_sqltasks_majordonor;
```

Next, you configure the task to assign the "Major Donors" tag to all donors in
this table. At the same time, we want to remove the tag from all contacts that
are *not* in this table. To do that, we can use the "Synchronize Tag" action.
For the "Contact Table" field, we use the table created by our script:
`temp_sqltasks_majordonor`.

Finally, select the "Major Donor" tag from the tag list and save your task using
"Create".
