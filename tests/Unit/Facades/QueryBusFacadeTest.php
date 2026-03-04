<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Facades;

use Cline\MessageBus\Facades\QueryBus;
use Cline\MessageBus\Queries\Contracts\QueryBusInterface;
use Cline\MessageBus\Queries\QueryBus as ConcreteQueryBus;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Config;
use Tests\Fixtures\Handlers\TestQueryHandler;
use Tests\Fixtures\Queries\TestQuery;

use function beforeEach;
use function describe;
use function expect;
use function resolve;
use function test;

describe('QueryBus Facade', function (): void {
    beforeEach(function (): void {
        // Register query handlers with Laravel dispatcher
        $dispatcher = resolve(Dispatcher::class);
        $dispatcher->map([
            TestQuery::class => TestQueryHandler::class,
        ]);

        Config::set('cqrs.query.middleware', []);
    });

    describe('Happy Paths', function (): void {
        test('resolves to QueryBusInterface implementation', function (): void {
            // Act
            $resolved = QueryBus::getFacadeRoot();

            // Assert
            expect($resolved)->toBeInstanceOf(QueryBusInterface::class);
            expect($resolved)->toBeInstanceOf(ConcreteQueryBus::class);
        });

        test('asks query through facade', function (): void {
            // Arrange
            $query = new TestQuery('facade_test');

            // Act
            $result = QueryBus::ask($query);

            // Assert
            expect($result)->toBeInstanceOf(TestQuery::class);
            expect($result->payload)->toBe('facade_test');
            expect($result->executionLog)->toContain('handler_executed');
        });

        test('applies middleware through facade', function (): void {
            // Arrange
            $query = new TestQuery('middleware_test');
            $middlewareExecuted = false;
            $middleware = function (object $query, callable $next) use (&$middlewareExecuted) {
                $middlewareExecuted = true;

                return $next($query);
            };

            // Act
            $result = QueryBus::withMiddleware($middleware)->ask($query);

            // Assert
            expect($middlewareExecuted)->toBeTrue();
            expect($result)->toBeInstanceOf(TestQuery::class);
        });
    });

    describe('Edge Cases', function (): void {
        test('multiple facade calls use same instance', function (): void {
            // Arrange
            $query1 = new TestQuery('test1');
            $query2 = new TestQuery('test2');

            // Act
            $result1 = QueryBus::ask($query1);
            $result2 = QueryBus::ask($query2);

            // Assert
            expect($result1->payload)->toBe('test1');
            expect($result2->payload)->toBe('test2');
            expect(QueryBus::getFacadeRoot())->toBe(QueryBus::getFacadeRoot());
        });

        test('facade resolves fresh when cleared', function (): void {
            // Arrange
            $instance1 = QueryBus::getFacadeRoot();

            // Act
            QueryBus::clearResolvedInstance(QueryBusInterface::class);
            $instance2 = QueryBus::getFacadeRoot();

            // Assert
            expect($instance1)->toBeInstanceOf(QueryBusInterface::class);
            expect($instance2)->toBeInstanceOf(QueryBusInterface::class);
        });
    });
});
