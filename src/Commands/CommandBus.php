<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\MessageBus\Commands;

use Cline\MessageBus\Commands\Contracts\CommandBusInterface;
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
final class CommandBus implements CommandBusInterface
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
        $middleware = $repository->get('cqrs.command.middleware', []);

        if (!is_array($middleware)) {
            $this->baseMiddleware = [];

            return;
        }

        /** @var array<int, callable|object|string> $validMiddleware */
        $validMiddleware = $middleware;
        $this->baseMiddleware = $validMiddleware;
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
            ->then(fn ($message) => $this->dispatcher->dispatchSync($message));
    }

    public function middleware(array|string|callable|object $middleware): self
    {
        /** @var array<int, callable|object|string> $merged */
        $merged = array_merge(
            $this->extraMiddleware,
            is_array($middleware) ? $middleware : [$middleware],
        );
        $this->extraMiddleware = $merged;

        return $this;
    }

    public function withMiddleware(array|string|callable|object $middleware): self
    {
        $clone = clone $this;

        /** @var array<int, callable|object|string> $merged */
        $merged = array_merge(
            $clone->scopedMiddleware,
            is_array($middleware) ? $middleware : [$middleware],
        );
        $clone->scopedMiddleware = $merged;

        return $clone;
    }
}
