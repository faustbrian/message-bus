<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolith\Legacy\Application\QueryHandler;

use Cline\MessageBus\Queries\Attributes\AsQueryHandler;

/**
 * Test fixture: Class-level query handler in legacy location
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[AsQueryHandler('Monolith\Queries\GetUserQuery')]
final class GetUserQueryHandler
{
    public function handle(): void
    {
        // Fixture class - no implementation needed
    }
}
