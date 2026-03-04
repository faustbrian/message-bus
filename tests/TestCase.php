<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use Cline\MessageBus\BusServiceProvider;
use Cline\MessageBus\Commands\CommandBus;
use Cline\MessageBus\Commands\Contracts\CommandBusInterface;
use Cline\MessageBus\Queries\Contracts\QueryBusInterface;
use Cline\MessageBus\Queries\QueryBus;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            BusServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Bind interfaces to concrete implementations
        $app->bind(CommandBusInterface::class, CommandBus::class);
        $app->bind(QueryBusInterface::class, QueryBus::class);
    }
}
