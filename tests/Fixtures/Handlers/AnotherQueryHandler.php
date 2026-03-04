<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Handlers;

use Tests\Fixtures\Queries\AnotherQuery;

/**
 * Alternative query handler fixture for QueryBus tests.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 *
 * @psalm-immutable
 */
final readonly class AnotherQueryHandler
{
    public function __invoke(AnotherQuery $query): string
    {
        return 'result: '.$query->data;
    }
}
