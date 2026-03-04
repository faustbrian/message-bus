<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Middleware;

use Closure;
use Tests\Fixtures\Queries\TestQuery;

/**
 * Middleware fixture that logs query execution.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 *
 * @psalm-immutable
 */
final readonly class QueryLoggingMiddleware
{
    public function __construct(
        private string $name,
    ) {}

    public function handle(TestQuery $query, Closure $next): mixed
    {
        $query = $query->addToLog('middleware_'.$this->name);

        return $next($query);
    }
}
