<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\MessageBus\Facades;

use Cline\MessageBus\Commands\Contracts\CommandBusInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed                                 dispatch(object $command)
 * @method static \Cline\MessageBus\Commands\CommandBus middleware(array<int, string|callable|object>|string|callable|object $middleware)
 * @method static \Cline\MessageBus\Commands\CommandBus withMiddleware(array<int, string|callable|object>|string|callable|object $middleware)
 *
 * @author Brian Faust <brian@cline.sh>
 * @see \Cline\MessageBus\Commands\CommandBus
 */
final class CommandBus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CommandBusInterface::class;
    }
}
