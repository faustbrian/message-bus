<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Facades;

use Cline\MessageBus\Commands\CommandBus as ConcreteCommandBus;
use Cline\MessageBus\Commands\Contracts\CommandBusInterface;
use Cline\MessageBus\Facades\CommandBus;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Commands\TestCommand;
use Tests\Fixtures\Handlers\TestCommandHandler;

use function beforeEach;
use function describe;
use function expect;
use function resolve;
use function test;

describe('CommandBus Facade', function (): void {
    beforeEach(function (): void {
        // Register command handlers with Laravel dispatcher
        $dispatcher = resolve(Dispatcher::class);
        $dispatcher->map([
            TestCommand::class => TestCommandHandler::class,
        ]);

        Config::set('cqrs.command.middleware', []);
    });

    describe('Happy Paths', function (): void {
        test('resolves to CommandBusInterface implementation', function (): void {
            // Act
            $resolved = CommandBus::getFacadeRoot();

            // Assert
            expect($resolved)->toBeInstanceOf(CommandBusInterface::class);
            expect($resolved)->toBeInstanceOf(ConcreteCommandBus::class);
        });

        test('dispatches command through facade', function (): void {
            // Arrange
            $command = new TestCommand('facade_test');

            // Act
            $result = CommandBus::dispatch($command);

            // Assert
            expect($result)->toBeInstanceOf(TestCommand::class);
            expect($result->payload)->toBe('facade_test');
            expect($result->executionLog)->toContain('handler_executed');
        });

        test('applies middleware through facade', function (): void {
            // Arrange
            $command = new TestCommand('middleware_test');
            $middlewareExecuted = false;
            $middleware = function (object $command, callable $next) use (&$middlewareExecuted) {
                $middlewareExecuted = true;

                return $next($command);
            };

            // Act
            $result = CommandBus::withMiddleware($middleware)->dispatch($command);

            // Assert
            expect($middlewareExecuted)->toBeTrue();
            expect($result)->toBeInstanceOf(TestCommand::class);
        });
    });

    describe('Edge Cases', function (): void {
        test('multiple facade calls use same instance', function (): void {
            // Arrange
            $command1 = new TestCommand('test1');
            $command2 = new TestCommand('test2');

            // Act
            $result1 = CommandBus::dispatch($command1);
            $result2 = CommandBus::dispatch($command2);

            // Assert
            expect($result1->payload)->toBe('test1');
            expect($result2->payload)->toBe('test2');
            expect(CommandBus::getFacadeRoot())->toBe(CommandBus::getFacadeRoot());
        });

        test('facade resolves fresh when cleared', function (): void {
            // Arrange
            $instance1 = CommandBus::getFacadeRoot();

            // Act
            CommandBus::clearResolvedInstance(CommandBusInterface::class);
            $instance2 = CommandBus::getFacadeRoot();

            // Assert
            expect($instance1)->toBeInstanceOf(CommandBusInterface::class);
            expect($instance2)->toBeInstanceOf(CommandBusInterface::class);
        });
    });
});
