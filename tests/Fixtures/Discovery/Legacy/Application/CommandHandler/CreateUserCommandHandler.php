<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolith\Legacy\Application\CommandHandler;

use Cline\MessageBus\Commands\Attributes\AsCommandHandler;

/**
 * Test fixture: Class-level command handler in legacy location
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[AsCommandHandler('Monolith\Commands\CreateUserCommand')]
final class CreateUserCommandHandler
{
    public function handle(): void
    {
        // Fixture class - no implementation needed
    }
}
