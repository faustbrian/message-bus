<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Commands;

/**
 * Another test command fixture for CommandBus tests.
 *
 * @internal
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class AnotherCommand
{
    public function __construct(
        public string $data,
    ) {}
}
