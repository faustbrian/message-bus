<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Queries;

use Cline\MessageBus\Queries\Support\AbstractQuery;

/**
 * Test query fixture for LogExecutionTimeMiddleware tests.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 *
 * @psalm-immutable
 */
final readonly class LogExecutionTimeTestQuery extends AbstractQuery
{
    public function __construct(
        public string $payload,
    ) {}
}
