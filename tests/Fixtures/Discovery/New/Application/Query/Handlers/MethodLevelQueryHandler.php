<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolith\Modern\Application\Query\Handlers;

use Cline\MessageBus\Queries\Attributes\AsQueryHandler;

/**
 * Test fixture: Method-level query handler
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MethodLevelQueryHandler
{
    #[AsQueryHandler('Monolith\Queries\FindUserQuery')]
    public function handleFindUser(): void
    {
        // Fixture class - no implementation needed
    }

    #[AsQueryHandler('Monolith\Queries\SearchUsersQuery')]
    public function handleSearchUsers(): void
    {
        // Fixture class - no implementation needed
    }
}
