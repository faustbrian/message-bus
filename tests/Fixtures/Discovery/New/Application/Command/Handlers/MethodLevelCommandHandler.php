<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolith\Modern\Application\Command\Handlers;

use Cline\MessageBus\Commands\Attributes\AsCommandHandler;

/**
 * Test fixture: Method-level command handler
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MethodLevelCommandHandler
{
    #[AsCommandHandler('Monolith\Commands\DeleteUserCommand')]
    public function handleDeleteUser(): void
    {
        // Fixture class - no implementation needed
    }

    #[AsCommandHandler('Monolith\Commands\ArchiveUserCommand')]
    public function handleArchiveUser(): void
    {
        // Fixture class - no implementation needed
    }
}
