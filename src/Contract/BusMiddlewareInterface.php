<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\MessageBus\Contract;

use Closure;

/**
 * Middleware interface for the Command/Query buses.
 *
 * Compatible with Laravel's Pipeline. Middlewares may run logic before and/or
 * after calling $next($message), mirroring Laravel HTTP middleware semantics.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface BusMiddlewareInterface
{
    /**
     * @param object  $message Command or Query DTO
     * @param Closure $next    Next stage in the pipeline
     */
    public function handle(object $message, Closure $next): mixed;
}
