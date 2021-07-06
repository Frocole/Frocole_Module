Setup:

Add Frocole database credentials to Drupal's /site/default/settings.php

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

The index of Frocole courses is available in Drupal at

    /admin/frocole/index

This page offers the options to Add a new Course and View, Delete and Edit existing ones.

Notes:
* As the database does not define SQL relations, deleting a course will not delete any groups or results. This renders those groups invalid. 
* Delete threfor is only usable when there are no groups (and users) attached to it.
* When entering 3..10 performance labels, separate them with forward slashes (e.g. /).
* Viewing a Course shows it's definition and a list of attached groups and users in those groups. The group ID link can be accessed to export the group's feedback items to a CSV file that can be used in Microsoft Excel®.
