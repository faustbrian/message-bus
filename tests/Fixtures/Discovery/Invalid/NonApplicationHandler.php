<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolith\Infrastructure\Handlers;

use Cline\MessageBus\Commands\Attributes\AsCommandHandler;

/**
 * Test fixture: Handler not in Application directory
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[AsCommandHandler('Monolith\Commands\InfrastructureCommand')]
final class NonApplicationHandler
{
    public function handle(): void
    {
        // Fixture class - no implementation needed
    }
}
