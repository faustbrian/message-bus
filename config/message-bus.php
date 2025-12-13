<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Discovery Cache Paths
    |--------------------------------------------------------------------------
    |
    | Central location for all cache files written by discovery commands and
    | providers. Adjust these if deploying with a non-standard filesystem
    | layout such as an ephemeral boot cache directory.
    |
    */
    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Command Handlers Map
        |--------------------------------------------------------------------------
        |
        | Path to the generated associative array mapping command classes to
        | their handler classes, produced by the "handlers:cache" command.
        | Consumed at boot by the BusServiceProvider to register the map with
        | Laravel’s dispatcher, avoiding runtime reflection in production.
        */
        'command_handlers' => base_path('bootstrap/cache/command-handlers.php'),
        /*
        |--------------------------------------------------------------------------
        | Query Handlers Map
        |--------------------------------------------------------------------------
        |
        | Path to the generated associative array mapping query classes to
        | their handler classes, produced by the "handlers:cache" command.
        | Loaded by the BusServiceProvider alongside command handlers to enable
        | fast query dispatch without discovery in production environments.
        */
        'query_handlers' => base_path('bootstrap/cache/query-handlers.php'),
    ],
];
