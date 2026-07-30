<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Options
    |--------------------------------------------------------------------------
    |
    | The allowed_methods and allowed_headers options are case-insensitive.
    |
    | You don't need to provide both allowed_origins and allowed_origins_patterns.
    | If one of the strings passed matches, it is considered a valid origin.
    |
    | If ['*'] is provided to allowed_methods, allowed_origins or allowed_headers
    | all methods / origins / headers are allowed.
    |
    */

    /*
     * You can enable CORS for 1 or multiple paths.
     * Example: ['api/*']
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'dist/img/*', 'dist/img/reports/*'],

    /*
    * Matches the request method. `['*']` allows all methods.
    */
    'allowed_methods' => ['*'],

    /*
     * Matches the request origin. `['*']` allows all origins. Wildcards can be used, eg `*.mydomain.com`
     */
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:8085',
        'http://192.168.0.36:8085',
        'http://localhost:4173',
        'http://127.0.0.1:4173',
        'http://localhost:5174',
        'http://127.0.0.1:5174',
        'http://localhost:5175',
        'http://127.0.0.1:5175',
        'http://localhost:8081',
        'http://127.0.0.1:8081',
        'http://localhost:8082',
        'http://127.0.0.1:8082',
        '*'
    ],

    /*
     * Patterns that can be used with `preg_match` to match the origin.
     */
    'allowed_origins_patterns' => [
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
    ],

    /*
     * Sets the Access-Control-Allow-Headers response header. `['*']` allows all headers.
     */
    'allowed_headers' => ['*'],

    /*
     * Sets the Access-Control-Expose-Headers response header with these headers.
     */
    'exposed_headers' => [],

    /*
     * Sets the Access-Control-Max-Age response header when > 0.
     */
    'max_age' => 0,

    /*
     * Sets the Access-Control-Allow-Credentials header.
     */
    'supports_credentials' => true,

];
