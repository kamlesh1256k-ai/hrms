<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'cashfree' => [
        'key' => '',
        'secret' => '',
        'url' => 'https://sandbox.cashfree.com/pg/orders',
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4-vision-preview'),
    ],

    /*
    | DeepFace (Python): clock-in / clock-out face verify via scripts/deepface_verify.py
    | pip install -r scripts/requirements-deepface.txt
    */
    'deepface' => [
        'enabled' => (bool) env('DEEPFACE_ENABLED', false),
        'python' => env('DEEPFACE_PYTHON', 'python'),
        'script' => env('DEEPFACE_SCRIPT', 'scripts/deepface_verify.py'),
        'timeout' => (float) env('DEEPFACE_TIMEOUT', 120),
        'model' => env('DEEPFACE_MODEL', 'Facenet512'),
        'detector_backend' => env('DEEPFACE_DETECTOR', 'opencv'),
        'fallback_openai' => (bool) env('DEEPFACE_FALLBACK_OPENAI', true),
    ],

];
