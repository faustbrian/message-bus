<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolith\Modern\Application\Services;

/**
 * This class is in /Application/ but NOT in any handler directory.
 * It should be skipped during discovery (line 68).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class NotInHandlerDirectory
{
    public function doSomething(): void
    {
        // Empty implementation
    }
}
