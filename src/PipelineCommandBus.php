<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\MessageBus;

use Cline\MessageBus\Contract\CommandBusInterface;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Pipeline;

use function array_merge;
use function is_array;

/**
 * Pipeline-based synchronous command bus.
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
#[Bind(CommandBusInterface::class)]
final class PipelineCommandBus implements CommandBusInterface
{
    /** @var array<int, callable|object|string> */
    private array $baseMiddleware;

    /** @var array<int, callable|object|string> */
    private array $extraMiddleware = [];

    /** @var array<int, callable|object|string> */
    private array $scopedMiddleware = [];

    public function __construct(
        private readonly Dispatcher $dispatcher,
        Repository $repository,
    ) {
        $this->baseMiddleware = (array) $repository->get('cqrs.command.middleware', []);
    }

    public function dispatch(object $command): mixed
    {
        $pipes = [
            ...$this->baseMiddleware,
            ...$this->extraMiddleware,
            ...$this->scopedMiddleware,
        ];

        $this->scopedMiddleware = [];

        return Pipeline::send($command)
            ->through($pipes)
            ->then(static fn ($message) => $this->dispatcher->dispatchSync($message));
    }

    public function middleware(array|string|callable|object $middleware): self
    {
        $this->extraMiddleware = array_merge(
            $this->extraMiddleware,
            is_array($middleware) ? $middleware : [$middleware],
        );

        return $this;
    }

    public function withMiddleware(array|string|callable|object $middleware): self
    {
        $clone = clone $this;
        $clone->scopedMiddleware = array_merge(
            $clone->scopedMiddleware,
            is_array($middleware) ? $middleware : [$middleware],
        );

        return $clone;
    }
}
