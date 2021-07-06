## Setup

Add **Frocole** database credentials to Drupal's /site/default/settings.php.

Example: 

    $databases['frocole']['default'] = array (
    'database' => 'frocole',
    'username' => 'mysql user',
    'password' => 'mysql pw',
    'prefix' => '',
    'host' => 'mysql hostname',
    'port' => '3306',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
    );

## Use
The list of **Frocole** courses is available in Drupal at

    /admin/frocole/index

This page offers the options to **Add** a new Course and **View**, **Delete** and **Edit** existing ones.

The **View** of a Course shows it's definition and a list of attached groups and users in those groups. 
The groupID link of each group in this **View** page can be used to accessed the export of the group's feedback items to a CSV file that can be used in Microsoft Excel®.

## Remarks:
* As the **Frocole** database does not define SQL relations or constraints, deleting a course will not delete any groups, users or feedback results but orphan them. This renders effectively those groups invalid. 
* Delete therefor can **ONLY** usable when there are no groups (and users) attached to it.
* When entering the 3 up to 10 performance labels, separate the axis labels with forward slashes (e.g. '/'). 
