<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'license' => [
            'driver' => 'local',
            'root' => storage_path('app/'),
        ],

        /*
         * 'visibility' + 'permissions' aggiunti qui (non c'erano):
         * Storage::makeDirectory()/putFileAs() chiamati senza ::disk(...)
         * in CBController/SettingsController/CRUDBooster.php usano questo
         * disco di default. Senza 'visibility' esplicita, Flysystem crea
         * cartelle/file con la visibilita' "private" di default (0700/0600
         * - solo il proprietario, niente per il gruppo del web server) a
         * prescindere dai parametri 0777 passati in quelle chiamate: il
         * metodo reale (FilesystemAdapter::makeDirectory($path), vedi
         * vendor) accetta un solo argomento, quelli extra sono ignorati da
         * PHP silenziosamente. Effetto visto in produzione: cartelle upload
         * mensili (uploads/{id}/{anno-mese}) inaccessibili a chiunque non
         * sia esattamente l'utente di sistema del web server.
         */
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'public',
            'permissions' => [
                'file' => ['public' => 0664, 'private' => 0600],
                'dir' => ['public' => 0775, 'private' => 0700],
            ],
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'public',
            'permissions' => [
                'file' => ['public' => 0664, 'private' => 0600],
                'dir' => ['public' => 0775, 'private' => 0700],
            ],
        ],

        's3' => [
            'driver' => 's3',
            'key' => 'your-key',
            'secret' => 'your-secret',
            'region' => 'your-region',
            'bucket' => 'your-bucket',
        ],

    ],

];
