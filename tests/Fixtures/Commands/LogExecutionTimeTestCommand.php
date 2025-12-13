<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Commands;

use Cline\MessageBus\Commands\Support\AbstractCommand;

/**
 * Test command fixture for LogExecutionTimeMiddleware tests.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 *
 * @psalm-immutable
 */
final readonly class LogExecutionTimeTestCommand extends AbstractCommand
{
    public function __construct(
        public string $payload,
    ) {}
}
