# Logstore Learning Analytics for Moodle

This plugin implements a logstore to log data used for analytics purposes. It is designed to work together with [`local_learning_analytics`](https://github.com/rwthanalytics/moodle-local_learning_analytics). To keep documentation in one place, you find all documentation in that repository.

Plugins:

- [`local_learning_analytics`](https://github.com/rwthanalytics/moodle-local_learning_analytics): User Interface
- [`logstore_lanalytics`](https://github.com/rwthanalytics/moodle-logstore_lanalytics): Logs the events to the database (this plugin)

Logging on an external Database:
Make sure you add and fill this Array (the entered values are just examples) in the moodle config.php before checking the option in the Lanalytics Admin Settings:
$CFG->logstore_lanalytics_external_database_logging = array (
  'externallogdbtype' => 'mariadb',
  'externallogdblibrary' => 'native',
  'externallogdbhost' => 'localhost',
  'externallogdbname' => 'testdb',
  'externallogdbtable' => 'logdata',
  'externallogdbuser' => 'root',
  'externallogdbpass' => '',
  'externallogprefix' => false, //use false if you dont use prefixes or 'myprefix' if you use them
  'externallogdboptions' => array (
    'dbcollation' => 'utf8mb4_unicode_ci',
    'dbport' => 3306,
  )
);


