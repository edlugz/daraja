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

    'consumer_key' => env('DARAJA_CONSUMER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Consumer Secret
    |--------------------------------------------------------------------------
    |
    | This value is the consumer secret provided for your developer application.
    | The package needs this to make requests to the MPESA APIs.
    |
    */

    'consumer_secret' => env('DARAJA_CONSUMER_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Initiator Name
    |--------------------------------------------------------------------------
    |
    | This value is the initiator name provided for your shortcode.
    | The package needs this to make requests to the MPESA APIs.
    |
    */

    'initiator_name' => env('DARAJA_INITIATOR_NAME', ''),

    /*
    |--------------------------------------------------------------------------
    | Initiator Password
    |--------------------------------------------------------------------------
    |
    | This value is the initiator password provided for your shortcode.
    | The package needs this to make requests to the MPESA APIs.
    |
    */

    'initiator_password' => env('DARAJA_INITIATOR_PASSWORD', ''),
    /*
    |--------------------------------------------------------------------------
    | Pass Key
    |--------------------------------------------------------------------------
    |
    | This value is the initiator password provided for your shortcode.
    | The package needs this to make requests to the MPESA APIs.
    |
    */

    'pass_key' => env('DARAJA_PASS_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Shortcode
    |--------------------------------------------------------------------------
    |
    | This value is the organisation shortcode/paybill/till.
    | The package needs this to make requests to the MPESA APIs.
    |
    */

    'shortcode' => env('DARAJA_SHORTCODE', ''),

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

    'base_url' => env('DARAJA_BASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Result URL
    |--------------------------------------------------------------------------
    |
    | Here you can set the URLs that will handle the results from each of the
    | APIs from MPESA
    |
    */

    'balance_result_url'                   => env('DARAJA_BALANCE_RESULT_URL', ''),
    'stk_result_url'                       => env('DARAJA_STK_RESULT_URL', ''),
    'mobile_result_url'                    => env('DARAJA_MOBILE_RESULT_URL', ''),
    'till_result_url'                      => env('DARAJA_TILL_RESULT_URL', ''),
    'paybill_result_url'                   => env('DARAJA_PAYBILL_RESULT_URL', ''),
    'reversal_result_url'                  => env('DARAJA_REVERSAL_RESULT_URL', ''),
    'transaction_query_result_url'         => env('DARAJA_TRANSACTION_QUERY_RESULT_URL', ''),
    'reversal_query_result_url'            => env('DARAJA_REVERSAL_QUERY_RESULT_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Timeout URL
    |--------------------------------------------------------------------------
    |
    | Here you can set the URLs that will handle the results from each of the
    | APIs from MPESA
    |
    */

    'timeout_url' => env('DARAJA_TIMEOUT_URL', ''),


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
        'level'   => 'DEBUG',
    ],

];
