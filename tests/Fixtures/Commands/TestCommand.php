<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Commands;

/**
 * Test command fixture for CommandBus tests.
 *
 * @internal
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class TestCommand
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
