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
 * Static callable middleware fixture.
 *
 * @internal
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class CallableMiddleware
{
    public static function handle(TestCommand $command, Closure $next): mixed
    {
        $command = $command->addToLog('callable_middleware');

        return $next($command);
    }
}
