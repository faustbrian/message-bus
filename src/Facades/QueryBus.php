<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\MessageBus\Facades;

use Cline\MessageBus\Queries\Contracts\QueryBusInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed                              ask(object $query)
 * @method static \Cline\MessageBus\Queries\QueryBus middleware(array<int, string|callable|object>|string|callable|object $middleware)
 * @method static \Cline\MessageBus\Queries\QueryBus withMiddleware(array<int, string|callable|object>|string|callable|object $middleware)
 *
 * @author Brian Faust <brian@cline.sh>
 * @see \Cline\MessageBus\Queries\QueryBus
 */
final class QueryBus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QueryBusInterface::class;
    }
}
