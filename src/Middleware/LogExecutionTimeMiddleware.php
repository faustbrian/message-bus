<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\MessageBus\Middleware;

use Cline\MessageBus\Commands\Support\AbstractCommand;
use Cline\MessageBus\Contracts\BusMiddlewareInterface;
use Cline\MessageBus\Queries\Support\AbstractQuery;
use Closure;
use Psr\Log\LoggerInterface;

use function mb_strtoupper;
use function microtime;
use function round;

/**
 * Logs the execution time of a command or query at debug level.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class LogExecutionTimeMiddleware implements BusMiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(object $message, Closure $next): mixed
    {
        $start = microtime(true);

        $result = $next($message);

        $elapsedMs = (microtime(true) - $start) * 1_000.0;

        $type = $message instanceof AbstractQuery ? 'query' : ($message instanceof AbstractCommand ? 'command' : 'message');
        $name = $message::class;

        $this->logger->debug('CQRS '.mb_strtoupper($type).' executed', [
            'message' => $name,
            'elapsed_ms' => round($elapsedMs, 2),
        ]);

        return $result;
    }
}
