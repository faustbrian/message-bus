<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Handlers;

use Tests\Fixtures\Commands\TestCommand;

/**
 * Test command handler fixture for CommandBus tests.
 *
 * @internal
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class TestCommandHandler
{
    public function __invoke(TestCommand $command): TestCommand
    {
        return $command->addToLog('handler_executed');
    }
}
