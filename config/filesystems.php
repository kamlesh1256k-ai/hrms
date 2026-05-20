<?php

$appUrl = rtrim(config('app.url', env('APP_URL', '')), '/');

return [

    /*
    |--------------------------------------------------------------------------
    | App URL (runtime-aware)
    |--------------------------------------------------------------------------
    |
    | AppServiceProvider may override config('app.url') per-request to match the
    | current host (useful for multi-domain/subdomain deployments). Use the
    | config value here (not env('APP_URL')) so Storage::url() follows it.
    |
    */

    'app_url' => $appUrl,

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('/'),
            'url' => $appUrl . '/storage',
            'visibility' => 'public',
        ],

        'public' => [
            'driver' => 'local',
            // Use public/storage directly because shared hosting (Hostinger)
            // disables PHP's symlink() function, so `php artisan storage:link`
            // cannot create the usual symlink. Writing here makes uploads
            // immediately accessible at APP_URL/storage/... with no symlink.
            'root' => public_path('storage'),
            'url' => $appUrl . '/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        'wasabi' => [
            'driver' => 's3',
            'key' => env('WAS_ACCESS_KEY_ID'),
            'secret' => env('WAS_SECRET_ACCESS_KEY'),
            'region' => env('WAS_DEFAULT_REGION'),
            'bucket' => env('WAS_BUCKET'),
            'endpoint' => env('WAS_URL')
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
