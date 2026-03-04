<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Queries;

/**
 * Test query fixture for QueryBus tests.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 *
 * @psalm-immutable
 */
final readonly class TestQuery
{
    public function __construct(
        public string $payload,
        public array $executionLog = [],
    ) {}

    public function addToLog(string $entry): self
    {
        return new self(
            $this->payload,
            [...$this->executionLog, $entry],
        );
    }
}
