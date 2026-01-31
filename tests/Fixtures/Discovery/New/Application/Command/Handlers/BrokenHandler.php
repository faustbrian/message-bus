<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolith\Modern\Application\Command\Handlers;

/**
 * This class is valid but should be excluded from the classmap in tests
 * to simulate a broken class that throws during ReflectionClass construction.
 * This triggers the catch block at lines 73-75.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class BrokenHandler
{
    public function handle(): void
    {
        // Empty implementation
    }
}
