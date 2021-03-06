<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'VBI' => [
        'hash_key' => env('PARTNER_SECRET_VBI_HASH_KEY', ''),
        'data_auth' => env('PARTNER_SECRET_VBI_URL_DATA_AUTHORITY', ''),
        'data_url' => env('PARTNER_SECRET_VBI_URL_DATA', ''),
    ],
];