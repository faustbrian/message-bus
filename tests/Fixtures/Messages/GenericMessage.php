<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Messages;

/**
 * Generic message fixture that doesn't extend AbstractCommand or AbstractQuery.
 *
 * @internal
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class GenericMessage
{
    public function __construct(
        public string $data,
    ) {}
}
