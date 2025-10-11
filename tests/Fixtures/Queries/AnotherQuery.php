<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Queries;

/**
 * Alternative query fixture for QueryBus tests.
 *
 * @internal
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class AnotherQuery
{
    public function __construct(
        public string $data,
    ) {}
}
