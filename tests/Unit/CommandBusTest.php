<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit;

use Cline\MessageBus\Commands\CommandBus;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Commands\AnotherCommand;
use Tests\Fixtures\Commands\TestCommand;
use Tests\Fixtures\Handlers\AnotherCommandHandler;
use Tests\Fixtures\Handlers\TestCommandHandler;
use Tests\Fixtures\Middleware\LoggingMiddleware;

use function app;
use function beforeEach;
use function describe;
use function expect;
use function resolve;
use function test;

describe('CommandBus', function (): void {
    beforeEach(function (): void {
        // Register command handlers with Laravel dispatcher
        $dispatcher = resolve(Dispatcher::class);
        $dispatcher->map([
            TestCommand::class => TestCommandHandler::class,
            AnotherCommand::class => AnotherCommandHandler::class,
        ]);
    });

    describe('Happy Paths', function (): void {
        test('dispatches command through pipeline and Laravel dispatcher', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);
            $command = new TestCommand('test_payload');

            // Act
            $result = $bus->dispatch($command);

            // Assert
            expect($result)->toBeInstanceOf(TestCommand::class);
            expect($result->payload)->toBe('test_payload');
            expect($result->executionLog)->toContain('handler_executed');
        });

        test('applies base middleware from config', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', [
                new LoggingMiddleware('base'),
            ]);
            $bus = resolve(CommandBus::class);
            $command = new TestCommand('test_payload');

            // Act
            $result = $bus->dispatch($command);

            // Assert
            expect($result->executionLog)->toBe([
                'middleware_base',
                'handler_executed',
            ]);
        });

        test('adds extra middleware via middleware() method that persists', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);
            $extraMiddleware = new LoggingMiddleware('extra');

            // Act
            $bus->middleware($extraMiddleware);
            $command1 = new TestCommand('first');
            $result1 = $bus->dispatch($command1);

            $command2 = new TestCommand('second');
            $result2 = $bus->dispatch($command2);

            // Assert - extra middleware persists across dispatches
            expect($result1->executionLog)->toBe([
                'middleware_extra',
                'handler_executed',
            ]);
            expect($result2->executionLog)->toBe([
                'middleware_extra',
                'handler_executed',
            ]);
        });

        test('creates clone with scoped middleware via withMiddleware()', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);
            $scopedMiddleware = new LoggingMiddleware('scoped');

            // Act
            $scopedBus = $bus->withMiddleware($scopedMiddleware);
            $command = new TestCommand('test_payload');
            $result = $scopedBus->dispatch($command);

            // Assert
            expect($scopedBus)->not->toBe($bus); // Different instances
            expect($result->executionLog)->toBe([
                'middleware_scoped',
                'handler_executed',
            ]);
        });

        test('resets scoped middleware after dispatch', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);
            $scopedMiddleware = new LoggingMiddleware('scoped');

            // Act
            $scopedBus = $bus->withMiddleware($scopedMiddleware);

            // First dispatch - scoped middleware applies
            $command1 = new TestCommand('first');
            $result1 = $scopedBus->dispatch($command1);

            // Second dispatch - scoped middleware should be reset
            $command2 = new TestCommand('second');
            $result2 = $scopedBus->dispatch($command2);

            // Assert
            expect($result1->executionLog)->toBe([
                'middleware_scoped',
                'handler_executed',
            ]);
            // Scoped middleware was reset after first dispatch
            expect($result2->executionLog)->toBe([
                'handler_executed',
            ]);
        });

        test('executes multiple middleware in correct order (base, extra, scoped)', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', [
                new LoggingMiddleware('base'),
            ]);
            $bus = resolve(CommandBus::class);

            $extraMiddleware = new LoggingMiddleware('extra');
            $bus->middleware($extraMiddleware);

            $scopedMiddleware = new LoggingMiddleware('scoped');
            $scopedBus = $bus->withMiddleware($scopedMiddleware);

            // Act
            $command = new TestCommand('test_payload');
            $result = $scopedBus->dispatch($command);

            // Assert - order: base → extra → scoped → handler
            expect($result->executionLog)->toBe([
                'middleware_base',
                'middleware_extra',
                'middleware_scoped',
                'handler_executed',
            ]);
        });

        test('executes multiple extra middleware in registration order', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);

            // Act - add multiple extra middleware
            $bus->middleware(
                new LoggingMiddleware('extra_1'),
            )
                ->middleware(
                    new LoggingMiddleware('extra_2'),
                )
                ->middleware(
                    new LoggingMiddleware('extra_3'),
                );

            $command = new TestCommand('test_payload');
            $result = $bus->dispatch($command);

            // Assert
            expect($result->executionLog)->toBe([
                'middleware_extra_1',
                'middleware_extra_2',
                'middleware_extra_3',
                'handler_executed',
            ]);
        });

        test('chains middleware() calls fluently', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);

            // Act
            $result = $bus
                ->middleware(
                    new LoggingMiddleware('first'),
                )
                ->middleware(
                    new LoggingMiddleware('second'),
                );

            // Assert
            expect($result)->toBe($bus); // Same instance returned
        });
    });

    describe('Sad Paths', function (): void {
        test('works correctly with empty middleware array', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);
            $command = new TestCommand('test_payload');

            // Act
            $result = $bus->dispatch($command);

            // Assert - command goes straight to handler
            expect($result->executionLog)->toBe([
                'handler_executed',
            ]);
        });

        test('handles missing config gracefully', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware');
            $bus = resolve(CommandBus::class);
            $command = new TestCommand('test_payload');

            // Act
            $result = $bus->dispatch($command);

            // Assert - works with empty middleware
            expect($result->executionLog)->toBe([
                'handler_executed',
            ]);
        });
    });

    describe('Edge Cases', function (): void {
        test('middleware() accepts string middleware class name', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);

            // Create instance and bind it for resolution
            $middlewareInstance = new LoggingMiddleware('string');
            app()->instance(LoggingMiddleware::class, $middlewareInstance);

            // Act
            $bus->middleware(LoggingMiddleware::class);
            $command = new TestCommand('test_payload');
            $result = $bus->dispatch($command);

            // Assert
            expect($result->executionLog)->toBe([
                'middleware_string',
                'handler_executed',
            ]);
        });

        test('middleware() accepts callable middleware', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);

            // Create a closure as callable middleware
            $callable = function ($command, $next) {
                $command = $command->addToLog('callable_middleware');

                return $next($command);
            };

            // Act
            $bus->middleware($callable);
            $command = new TestCommand('test_payload');
            $result = $bus->dispatch($command);

            // Assert
            expect($result->executionLog)->toBe([
                'callable_middleware',
                'handler_executed',
            ]);
        });

        test('middleware() accepts object middleware instance', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);

            $middleware = new LoggingMiddleware('object');

            // Act
            $bus->middleware($middleware);
            $command = new TestCommand('test_payload');
            $result = $bus->dispatch($command);

            // Assert
            expect($result->executionLog)->toBe([
                'middleware_object',
                'handler_executed',
            ]);
        });

        test('middleware() accepts array of middleware', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);

            $middlewareArray = [
                new LoggingMiddleware('array_1'),
                new LoggingMiddleware('array_2'),
            ];

            // Act
            $bus->middleware($middlewareArray);
            $command = new TestCommand('test_payload');
            $result = $bus->dispatch($command);

            // Assert
            expect($result->executionLog)->toBe([
                'middleware_array_1',
                'middleware_array_2',
                'handler_executed',
            ]);
        });

        test('withMiddleware() does not affect original instance', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $originalBus = resolve(CommandBus::class);
            $scopedMiddleware = new LoggingMiddleware('scoped');

            // Act
            $scopedBus = $originalBus->withMiddleware($scopedMiddleware);

            // Dispatch on scoped bus
            $command1 = new TestCommand('scoped');
            $result1 = $scopedBus->dispatch($command1);

            // Dispatch on original bus
            $command2 = new TestCommand('original');
            $result2 = $originalBus->dispatch($command2);

            // Assert
            expect($result1->executionLog)->toBe([
                'middleware_scoped',
                'handler_executed',
            ]);
            expect($result2->executionLog)->toBe([
                'handler_executed',
            ]); // Original bus not affected
        });

        test('multiple withMiddleware() calls create independent clones', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);

            // Act
            $scopedBus1 = $bus->withMiddleware(
                new LoggingMiddleware('scoped_1'),
            );
            $scopedBus2 = $bus->withMiddleware(
                new LoggingMiddleware('scoped_2'),
            );

            $command1 = new TestCommand('first');
            $result1 = $scopedBus1->dispatch($command1);

            $command2 = new TestCommand('second');
            $result2 = $scopedBus2->dispatch($command2);

            // Assert - each clone has its own scoped middleware
            expect($result1->executionLog)->toBe([
                'middleware_scoped_1',
                'handler_executed',
            ]);
            expect($result2->executionLog)->toBe([
                'middleware_scoped_2',
                'handler_executed',
            ]);
        });

        test('withMiddleware() on cloned bus preserves extra middleware', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);
            $bus->middleware(
                new LoggingMiddleware('extra'),
            );

            // Act
            $scopedBus = $bus->withMiddleware(
                new LoggingMiddleware('scoped'),
            );
            $command = new TestCommand('test_payload');
            $result = $scopedBus->dispatch($command);

            // Assert - both extra and scoped middleware present
            expect($result->executionLog)->toBe([
                'middleware_extra',
                'middleware_scoped',
                'handler_executed',
            ]);
        });

        test('handles different command types correctly', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);

            // Act
            $command = new AnotherCommand('test_data');
            $result = $bus->dispatch($command);

            // Assert
            expect($result)->toBe('processed: test_data');
        });

        test('empty array middleware works with all bus methods', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);

            // Act - add empty array as extra middleware
            $bus->middleware([]);

            $scopedBus = $bus->withMiddleware([]);

            $command = new TestCommand('test_payload');
            $result = $scopedBus->dispatch($command);

            // Assert
            expect($result->executionLog)->toBe([
                'handler_executed',
            ]);
        });
    });

    describe('Regressions', function (): void {
        test('scoped middleware only applies to cloned instance', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $originalBus = resolve(CommandBus::class);

            // Act
            $scopedBus = $originalBus->withMiddleware(
                new LoggingMiddleware('scoped'),
            );

            // Dispatch multiple times on both buses
            $command1 = new TestCommand('scoped_1');
            $result1 = $scopedBus->dispatch($command1);

            $command2 = new TestCommand('original_1');
            $result2 = $originalBus->dispatch($command2);

            $command3 = new TestCommand('scoped_2');
            $result3 = $scopedBus->dispatch($command3);

            $command4 = new TestCommand('original_2');
            $result4 = $originalBus->dispatch($command4);

            // Assert
            // First scoped dispatch has middleware
            expect($result1->executionLog)->toBe([
                'middleware_scoped',
                'handler_executed',
            ]);

            // Original bus never has scoped middleware
            expect($result2->executionLog)->toBe(['handler_executed']);
            expect($result4->executionLog)->toBe(['handler_executed']);

            // Second scoped dispatch - scoped middleware reset after first dispatch
            expect($result3->executionLog)->toBe(['handler_executed']);
        });

        test('extra middleware persists after scoped middleware reset', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', []);
            $bus = resolve(CommandBus::class);
            $bus->middleware(
                new LoggingMiddleware('extra'),
            );

            // Act
            $scopedBus = $bus->withMiddleware(
                new LoggingMiddleware('scoped'),
            );

            // First dispatch - has both extra and scoped
            $command1 = new TestCommand('first');
            $result1 = $scopedBus->dispatch($command1);

            // Second dispatch - scoped reset, extra persists
            $command2 = new TestCommand('second');
            $result2 = $scopedBus->dispatch($command2);

            // Assert
            expect($result1->executionLog)->toBe([
                'middleware_extra',
                'middleware_scoped',
                'handler_executed',
            ]);
            expect($result2->executionLog)->toBe([
                'middleware_extra',
                'handler_executed',
            ]);
        });

        test('base middleware always executes regardless of extra or scoped middleware', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', [
                new LoggingMiddleware('base'),
            ]);
            $bus = resolve(CommandBus::class);

            // Act - dispatch with no extra middleware
            $command1 = new TestCommand('no_extra');
            $result1 = $bus->dispatch($command1);

            // Add extra middleware
            $bus->middleware(
                new LoggingMiddleware('extra'),
            );
            $command2 = new TestCommand('with_extra');
            $result2 = $bus->dispatch($command2);

            // Use scoped middleware
            $scopedBus = $bus->withMiddleware(
                new LoggingMiddleware('scoped'),
            );
            $command3 = new TestCommand('with_scoped');
            $result3 = $scopedBus->dispatch($command3);

            // Assert - base middleware present in all
            expect($result1->executionLog)->toBe([
                'middleware_base',
                'handler_executed',
            ]);
            expect($result2->executionLog)->toBe([
                'middleware_base',
                'middleware_extra',
                'handler_executed',
            ]);
            expect($result3->executionLog)->toBe([
                'middleware_base',
                'middleware_extra',
                'middleware_scoped',
                'handler_executed',
            ]);
        });

        test('cloning creates independent middleware arrays', function (): void {
            // Arrange
            Config::set('cqrs.command.middleware', [
                new LoggingMiddleware('base'),
            ]);
            $bus = resolve(CommandBus::class);
            $bus->middleware(
                new LoggingMiddleware('extra'),
            );

            // Act
            $scopedBus = $bus->withMiddleware(
                new LoggingMiddleware('scoped'),
            );

            // Modify original bus after cloning
            $bus->middleware(
                new LoggingMiddleware('new_extra'),
            );

            // Dispatch on scoped bus
            $command = new TestCommand('test_payload');
            $result = $scopedBus->dispatch($command);

            // Assert - cloned bus has independent arrays, so new_extra doesn't appear
            expect($result->executionLog)->toBe([
                'middleware_base',
                'middleware_extra',
                'middleware_scoped',
                'handler_executed',
            ]);

            // Dispatch on original bus to verify new_extra is there
            $command2 = new TestCommand('original');
            $result2 = $bus->dispatch($command2);
            expect($result2->executionLog)->toBe([
                'middleware_base',
                'middleware_extra',
                'middleware_new_extra',
                'handler_executed',
            ]);
        });
    });
});
