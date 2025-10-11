<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Middleware;

use Closure;
use Tests\Fixtures\Commands\TestCommand;

/**
 * Middleware fixture that logs its execution.
 *
 * @internal
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class LoggingMiddleware
{
    public function __construct(
        private string $name,
    ) {}

    public function handle(TestCommand $command, Closure $next): mixed
    {
        $command = $command->addToLog('middleware_'.$this->name);

        return $next($command);
    }
}
