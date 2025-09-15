<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\MessageBus\Attribute;

use Attribute;

/**
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class AsCommandHandler
{
    /**
     * @param class-string $command
     */
    public function __construct(
        public string $command,
    ) {}
}
