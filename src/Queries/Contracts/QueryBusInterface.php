<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\MessageBus\Queries\Contracts;

/**
 * @author Brian Faust <brian@cline.sh>
 */
interface QueryBusInterface
{
    /**
     * Ask a query synchronously.
     */
    public function ask(object $query): mixed;

    /**
     * Append middleware to this bus instance.
     *
     * Accepts class-strings, callables, or instantiated middleware objects
     * compatible with Laravel's Pipeline (handle/__invoke signature).
     * Returns the same instance for chaining.
     *
     * @param array<int, callable|object|string>|callable|object|string $middleware
     */
    public function middleware(array|string|callable|object $middleware): self;

    /**
     * Clone the bus with extra middleware applied for the next call only.
     *
     * @param array<int, callable|object|string>|callable|object|string $middleware
     */
    public function withMiddleware(array|string|callable|object $middleware): self;
}
