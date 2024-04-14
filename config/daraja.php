<?php

return [

    /*
   |--------------------------------------------------------------------------
   | Consumer Key
   |--------------------------------------------------------------------------
   |
   | This value is the consumer key provided for your developer application.
   | The package needs this to make requests to the MPESA APIs.
   |
   */

    'consumer_key' => env('CONSUMER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Consumer Secret
    |--------------------------------------------------------------------------
    |
    | This value is the consumer secret provided for your developer application.
    | The package needs this to make requests to the MPESA APIs.
    |
    */

    'consumer_secret' => env('CONSUMER_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Initiator Name
    |--------------------------------------------------------------------------
    |
    | This value is the initiator name provided for your shortcode.
    | The package needs this to make requests to the MPESA APIs.
    |
    */

    'initiator_name' => env('INITIATOR_NAME', ''),

    /*
    |--------------------------------------------------------------------------
    | Initiator Password
    |--------------------------------------------------------------------------
    |
    | This value is the initiator password provided for your shortcode.
    | The package needs this to make requests to the MPESA APIs.
    |
    */

    'initiator_password' => env('INITIATOR_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Shortcode
    |--------------------------------------------------------------------------
    |
    | This value is the organisation shortcode/paybill/till.
    | The package needs this to make requests to the MPESA APIs.
    |
    */

    'shortcode' => env('SHORTCODE', ''),

    /*
    |--------------------------------------------------------------------------
    | Package Mode
    |--------------------------------------------------------------------------
    |
    | This value sets the mode at which you are using the package. Acceptable
    | values are sandbox or production
    |
    */

    'mode' => 'live',

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | Here you can set the MPESA Base URL
    |
    */

    'base_url' => env('BASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Result URL
    |--------------------------------------------------------------------------
    |
    | Here you can set the URLs that will handle the results from each of the
    | APIs from MPESA
    |
    */

    'result_url' => env('RESULT_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Timeout URL
    |--------------------------------------------------------------------------
    |
    | Here you can set the URLs that will handle the results from each of the
    | APIs from MPESA
    |
    */

    'timeout_url' => env('TIMEOUT_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Balance URL
    |--------------------------------------------------------------------------
    |
    | Here you can set the URLs that will handle the results from each of the
    | APIs from MPESA
    |
    */

    'balance_url' => env('BALANCE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | LOGS
    |--------------------------------------------------------------------------
    |
    | Here you can set your logging requirements. If enabled a new file will
    | will be created in the logs folder and will record all requests
    | and responses to the MPESA APIs. You can use the
    | the Monolog debug levels.
    |
    */

    'logs' => [
        'enabled' => true,
        'level' => 'DEBUG',
    ],

];
