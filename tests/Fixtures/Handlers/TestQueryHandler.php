<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Handlers;

use Tests\Fixtures\Queries\TestQuery;

/**
 * Test query handler fixture for QueryBus tests.
 *
 * @internal
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class TestQueryHandler
{
    public function __invoke(TestQuery $query): TestQuery
    {
        return $query->addToLog('handler_executed');
    }
}
