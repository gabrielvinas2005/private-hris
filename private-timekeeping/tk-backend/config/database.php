<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlsrv'),

    /*
    | Optional: Laravel connection name used for attendance process run progress
    | (defaults by driver — see ProcessAttendanceController).
    */
    'attendance_process_progress_connection' => env('ATTENDANCE_PROGRESS_DB_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'attendance_process_progress_mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', ''),
            'port' => env('DB_PORT', ''),
            'database' => env('DB_DATABASE', ''),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'appname' => env('APP_NAME', ''),
            'encrypt' => env('DB_ENCRYPT', 'no'),
            'Trust_Server_Certificate' => true,
        ],

        /*
        | Same server/database as the default connection, but a separate PDO pool so
        | attendance_process_runs progress updates auto-commit while the main request
        | holds a long transaction (process attendance). The UI polls this table.
        */
        'attendance_process_progress' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', ''),
            'port' => env('DB_PORT', ''),
            'database' => env('DB_DATABASE', ''),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'appname' => env('APP_NAME', '') . '_progress',
            'encrypt' => env('DB_ENCRYPT', 'no'),
            'Trust_Server_Certificate' => true,
        ],

        'sqlsrv_mig' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_MIG', ''),
            'port' => env('DB_PORT_MIG', ''),
            'database' => env('DB_DATABASE_MIG', ''),
            'username' => env('DB_USERNAME_MIG', ''),
            'password' => env('DB_PASSWORD_MIG', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'appname' => env('APP_NAME', ''),
            'encrypt' => env('DB_ENCRYPT', 'no'),
            'Trust_Server_Certificate' => true,
        ],

        'attachments' => [
            'driver' => 'sqlsrv',
            'url' => env('ATTACHMENTS_DATABASE_URL'),
            'host' => env('ATTACHMENTS_DB_HOST', env('DB_HOST', '')),
            'port' => env('ATTACHMENTS_DB_PORT', env('DB_PORT', '1433')),
            'database' => env('ATTACHMENTS_DB_DATABASE', 'WTIHRIS_PTTC_ATTACHMENTS'),
            'username' => env('ATTACHMENTS_DB_USERNAME', env('DB_USERNAME', '')),
            'password' => env('ATTACHMENTS_DB_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'appname' => env('APP_NAME', ''),
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', true),
        ],

        'anviz' => [
            'driver' => env('DB_CONNECTION', ''),
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_BIO', ''),
            'port' => env('DB_PORT_BIO', ''),
            'database' => env('DB_DATABASE_BIO', ''),
            'username' => env('DB_USERNAME_BIO', ''),
            'password' => env('DB_PASSWORD_BIO', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            // 'appname' => env('APP_NAME', ''),
            // 'encrypt' => env('ANVIZ_DB_ENCRYPT', 'no'),
            'Trust_Server_Certificate' => env('DB_TRUST_SERVER_CERTIFICATE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
