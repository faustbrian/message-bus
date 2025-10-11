<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit;

use Cline\MessageBus\Queries\QueryBus;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Handlers\AnotherQueryHandler;
use Tests\Fixtures\Handlers\TestQueryHandler;
use Tests\Fixtures\Middleware\QueryLoggingMiddleware;
use Tests\Fixtures\Queries\AnotherQuery;
use Tests\Fixtures\Queries\TestQuery;

use function app;
use function beforeEach;
use function describe;
use function expect;
use function test;

describe('QueryBus', function (): void {
    beforeEach(function (): void {
        // Register query handlers with Laravel dispatcher
        $dispatcher = app(Dispatcher::class);
        $dispatcher->map([
            TestQuery::class => TestQueryHandler::class,
            AnotherQuery::class => AnotherQueryHandler::class,
        ]);
    });

    describe('Happy Paths', function (): void {
        test('executes query through pipeline and dispatcher using ask method', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);
            $query = new TestQuery('test_payload');

            // Act
            $result = $bus->ask($query);

            // Assert
            expect($result)->toBeInstanceOf(TestQuery::class);
            expect($result->payload)->toBe('test_payload');
            expect($result->executionLog)->toContain('handler_executed');
        });

        test('applies base middleware from config', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', [
                new QueryLoggingMiddleware('base'),
            ]);
            $bus = app(QueryBus::class);
            $query = new TestQuery('test_payload');

            // Act
            $result = $bus->ask($query);

            // Assert
            expect($result->executionLog)->toBe([
                'middleware_base',
                'handler_executed',
            ]);
        });

        test('adds extra middleware via middleware method that persists', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);
            $extraMiddleware = new QueryLoggingMiddleware('extra');

            // Act
            $bus->middleware($extraMiddleware);
            $query1 = new TestQuery('first');
            $result1 = $bus->ask($query1);

            $query2 = new TestQuery('second');
            $result2 = $bus->ask($query2);

            // Assert - extra middleware persists across asks
            expect($result1->executionLog)->toBe([
                'middleware_extra',
                'handler_executed',
            ]);
            expect($result2->executionLog)->toBe([
                'middleware_extra',
                'handler_executed',
            ]);
        });

        test('creates clone with scoped middleware via withMiddleware', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);
            $scopedMiddleware = new QueryLoggingMiddleware('scoped');

            // Act
            $scopedBus = $bus->withMiddleware($scopedMiddleware);
            $query = new TestQuery('test_payload');
            $result = $scopedBus->ask($query);

            // Assert
            expect($scopedBus)->not->toBe($bus); // Different instances
            expect($result->executionLog)->toBe([
                'middleware_scoped',
                'handler_executed',
            ]);
        });

        test('resets scoped middleware after ask', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);
            $scopedMiddleware = new QueryLoggingMiddleware('scoped');

            // Act
            $scopedBus = $bus->withMiddleware($scopedMiddleware);

            // First ask - scoped middleware applies
            $query1 = new TestQuery('first');
            $result1 = $scopedBus->ask($query1);

            // Second ask - scoped middleware should be reset
            $query2 = new TestQuery('second');
            $result2 = $scopedBus->ask($query2);

            // Assert
            expect($result1->executionLog)->toBe([
                'middleware_scoped',
                'handler_executed',
            ]);
            // Scoped middleware was reset after first ask
            expect($result2->executionLog)->toBe([
                'handler_executed',
            ]);
        });

        test('executes multiple middleware in correct order (base, extra, scoped)', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', [
                new QueryLoggingMiddleware('base'),
            ]);
            $bus = app(QueryBus::class);

            $extraMiddleware = new QueryLoggingMiddleware('extra');
            $bus->middleware($extraMiddleware);

            $scopedMiddleware = new QueryLoggingMiddleware('scoped');
            $scopedBus = $bus->withMiddleware($scopedMiddleware);

            // Act
            $query = new TestQuery('test_payload');
            $result = $scopedBus->ask($query);

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
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);

            // Act - add multiple extra middleware
            $bus->middleware(
                new QueryLoggingMiddleware('extra_1'),
            )
                ->middleware(
                    new QueryLoggingMiddleware('extra_2'),
                )
                ->middleware(
                    new QueryLoggingMiddleware('extra_3'),
                );

            $query = new TestQuery('test_payload');
            $result = $bus->ask($query);

            // Assert
            expect($result->executionLog)->toBe([
                'middleware_extra_1',
                'middleware_extra_2',
                'middleware_extra_3',
                'handler_executed',
            ]);
        });

        test('chains middleware calls fluently', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);

            // Act
            $result = $bus
                ->middleware(
                    new QueryLoggingMiddleware('first'),
                )
                ->middleware(
                    new QueryLoggingMiddleware('second'),
                );

            // Assert
            expect($result)->toBe($bus); // Same instance returned
        });
    });

    describe('Sad Paths', function (): void {
        test('works correctly with empty middleware array', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);
            $query = new TestQuery('test_payload');

            // Act
            $result = $bus->ask($query);

            // Assert - query goes straight to handler
            expect($result->executionLog)->toBe([
                'handler_executed',
            ]);
        });

        test('handles missing config gracefully', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', null);
            $bus = app(QueryBus::class);
            $query = new TestQuery('test_payload');

            // Act
            $result = $bus->ask($query);

            // Assert - works with empty middleware
            expect($result->executionLog)->toBe([
                'handler_executed',
            ]);
        });
    });

    describe('Edge Cases', function (): void {
        test('middleware accepts string middleware class name', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);

            // Create instance and bind it for resolution
            $middlewareInstance = new QueryLoggingMiddleware('string');
            app()->instance(QueryLoggingMiddleware::class, $middlewareInstance);

            // Act
            $bus->middleware(QueryLoggingMiddleware::class);
            $query = new TestQuery('test_payload');
            $result = $bus->ask($query);

            // Assert
            expect($result->executionLog)->toBe([
                'middleware_string',
                'handler_executed',
            ]);
        });

        test('middleware accepts callable middleware', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);

            // Create a closure as callable middleware
            $callable = function ($query, $next) {
                $query = $query->addToLog('callable_middleware');

                return $next($query);
            };

            // Act
            $bus->middleware($callable);
            $query = new TestQuery('test_payload');
            $result = $bus->ask($query);

            // Assert
            expect($result->executionLog)->toBe([
                'callable_middleware',
                'handler_executed',
            ]);
        });

        test('middleware accepts object middleware instance', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);

            $middleware = new QueryLoggingMiddleware('object');

            // Act
            $bus->middleware($middleware);
            $query = new TestQuery('test_payload');
            $result = $bus->ask($query);

            // Assert
            expect($result->executionLog)->toBe([
                'middleware_object',
                'handler_executed',
            ]);
        });

        test('middleware accepts array of middleware', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);

            $middlewareArray = [
                new QueryLoggingMiddleware('array_1'),
                new QueryLoggingMiddleware('array_2'),
            ];

            // Act
            $bus->middleware($middlewareArray);
            $query = new TestQuery('test_payload');
            $result = $bus->ask($query);

            // Assert
            expect($result->executionLog)->toBe([
                'middleware_array_1',
                'middleware_array_2',
                'handler_executed',
            ]);
        });

        test('withMiddleware does not affect original instance', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $originalBus = app(QueryBus::class);
            $scopedMiddleware = new QueryLoggingMiddleware('scoped');

            // Act
            $scopedBus = $originalBus->withMiddleware($scopedMiddleware);

            // Ask on scoped bus
            $query1 = new TestQuery('scoped');
            $result1 = $scopedBus->ask($query1);

            // Ask on original bus
            $query2 = new TestQuery('original');
            $result2 = $originalBus->ask($query2);

            // Assert
            expect($result1->executionLog)->toBe([
                'middleware_scoped',
                'handler_executed',
            ]);
            expect($result2->executionLog)->toBe([
                'handler_executed',
            ]); // Original bus not affected
        });

        test('multiple withMiddleware calls create independent clones', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);

            // Act
            $scopedBus1 = $bus->withMiddleware(
                new QueryLoggingMiddleware('scoped_1'),
            );
            $scopedBus2 = $bus->withMiddleware(
                new QueryLoggingMiddleware('scoped_2'),
            );

            $query1 = new TestQuery('first');
            $result1 = $scopedBus1->ask($query1);

            $query2 = new TestQuery('second');
            $result2 = $scopedBus2->ask($query2);

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

        test('withMiddleware on cloned bus preserves extra middleware', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);
            $bus->middleware(
                new QueryLoggingMiddleware('extra'),
            );

            // Act
            $scopedBus = $bus->withMiddleware(
                new QueryLoggingMiddleware('scoped'),
            );
            $query = new TestQuery('test_payload');
            $result = $scopedBus->ask($query);

            // Assert - both extra and scoped middleware present
            expect($result->executionLog)->toBe([
                'middleware_extra',
                'middleware_scoped',
                'handler_executed',
            ]);
        });

        test('handles different query types correctly', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);

            // Act
            $query = new AnotherQuery('test_data');
            $result = $bus->ask($query);

            // Assert
            expect($result)->toBe('result: test_data');
        });

        test('empty array middleware works with all bus methods', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);

            // Act - add empty array as extra middleware
            $bus->middleware([]);

            $scopedBus = $bus->withMiddleware([]);

            $query = new TestQuery('test_payload');
            $result = $scopedBus->ask($query);

            // Assert
            expect($result->executionLog)->toBe([
                'handler_executed',
            ]);
        });
    });

    describe('Regressions', function (): void {
        test('scoped middleware only applies to cloned instance', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $originalBus = app(QueryBus::class);

            // Act
            $scopedBus = $originalBus->withMiddleware(
                new QueryLoggingMiddleware('scoped'),
            );

            // Ask multiple times on both buses
            $query1 = new TestQuery('scoped_1');
            $result1 = $scopedBus->ask($query1);

            $query2 = new TestQuery('original_1');
            $result2 = $originalBus->ask($query2);

            $query3 = new TestQuery('scoped_2');
            $result3 = $scopedBus->ask($query3);

            $query4 = new TestQuery('original_2');
            $result4 = $originalBus->ask($query4);

            // Assert
            // First scoped ask has middleware
            expect($result1->executionLog)->toBe([
                'middleware_scoped',
                'handler_executed',
            ]);

            // Original bus never has scoped middleware
            expect($result2->executionLog)->toBe(['handler_executed']);
            expect($result4->executionLog)->toBe(['handler_executed']);

            // Second scoped ask - scoped middleware reset after first ask
            expect($result3->executionLog)->toBe(['handler_executed']);
        });

        test('extra middleware persists after scoped middleware reset', function (): void {
            // Arrange
            Config::set('cqrs.query.middleware', []);
            $bus = app(QueryBus::class);
            $bus->middleware(
                new QueryLoggingMiddleware('extra'),
            );

            // Act
            $scopedBus = $bus->withMiddleware(
                new QueryLoggingMiddleware('scoped'),
            );

            // First ask - has both extra and scoped
            $query1 = new TestQuery('first');
            $result1 = $scopedBus->ask($query1);

            // Second ask - scoped reset, extra persists
            $query2 = new TestQuery('second');
            $result2 = $scopedBus->ask($query2);

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
            Config::set('cqrs.query.middleware', [
                new QueryLoggingMiddleware('base'),
            ]);
            $bus = app(QueryBus::class);

            // Act - ask with no extra middleware
            $query1 = new TestQuery('no_extra');
            $result1 = $bus->ask($query1);

            // Add extra middleware
            $bus->middleware(
                new QueryLoggingMiddleware('extra'),
            );
            $query2 = new TestQuery('with_extra');
            $result2 = $bus->ask($query2);

            // Use scoped middleware
            $scopedBus = $bus->withMiddleware(
                new QueryLoggingMiddleware('scoped'),
            );
            $query3 = new TestQuery('with_scoped');
            $result3 = $scopedBus->ask($query3);

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
            Config::set('cqrs.query.middleware', [
                new QueryLoggingMiddleware('base'),
            ]);
            $bus = app(QueryBus::class);
            $bus->middleware(
                new QueryLoggingMiddleware('extra'),
            );

            // Act
            $scopedBus = $bus->withMiddleware(
                new QueryLoggingMiddleware('scoped'),
            );

            // Modify original bus after cloning
            $bus->middleware(
                new QueryLoggingMiddleware('new_extra'),
            );

            // Ask on scoped bus
            $query = new TestQuery('test_payload');
            $result = $scopedBus->ask($query);

            // Assert - cloned bus has independent arrays, so new_extra doesn't appear
            expect($result->executionLog)->toBe([
                'middleware_base',
                'middleware_extra',
                'middleware_scoped',
                'handler_executed',
            ]);

            // Ask on original bus to verify new_extra is there
            $query2 = new TestQuery('original');
            $result2 = $bus->ask($query2);
            expect($result2->executionLog)->toBe([
                'middleware_base',
                'middleware_extra',
                'middleware_new_extra',
                'handler_executed',
            ]);
        });
    });
});
