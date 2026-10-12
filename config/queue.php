<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for each one. Here you may set the default queue driver.
    |
    | Supported: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'default' => env('QUEUE_DRIVER', 'sync'),

    /*
    | Connessione delle email accodate (avvisi di scadenza utente, vedi
    | App\Jobs\SendTemplateEmailJob). 'sync' = invio immediato nel comando,
    | come prima; 'mail' = in coda 'mail', serve un worker attivo che la
    | serva (queue:work qlik_sync --queue=qlik_sync,mail: la tabella 'jobs' e'
    | la stessa, il worker legge per nome di coda).
    */
    'mail_connection' => env('MAIL_QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],

        // Connessione dedicata alla sincronizzazione app/item Qlik (vedi
        // docs/piano-qlik-sync-app-items.md). retry_after molto piu' alto del
        // default: un import lungo non deve essere rimesso in coda mentre gira.
        // Il default globale resta 'sync': nient'altro usa le code.
        'qlik_sync' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'qlik_sync',
            'retry_after' => 3900,
        ],

        // Email in coda (avvisi di scadenza, email ritardate): stessa tabella
        // 'jobs' del driver database, coda 'mail'. Il nome della connessione
        // serve solo a chi accoda; a prenderle e' il worker che serve la coda
        // 'mail' (oggi quello di qlik_sync, il cui retry_after e' quello che
        // vale per un job rimasto appeso).
        'mail' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'mail',
            'retry_after' => 90,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => 'your-public-key',
            'secret' => 'your-secret-key',
            'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
            'queue' => 'your-queue-name',
            'region' => 'us-east-1',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'default',
            'retry_after' => 90,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
