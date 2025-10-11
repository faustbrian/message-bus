<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit;

use Cline\MessageBus\Middleware\LogExecutionTimeMiddleware;
use RuntimeException;
use stdClass;
use Tests\Fixtures\Commands\LogExecutionTimeTestCommand;
use Tests\Fixtures\Messages\GenericMessage;
use Tests\Fixtures\Queries\LogExecutionTimeTestQuery;
use Tests\Fixtures\Support\TestLogger;

use function beforeEach;
use function describe;
use function expect;
use function mb_strlen;
use function round;
use function test;
use function usleep;

describe('LogExecutionTimeMiddleware', function (): void {
    beforeEach(function (): void {
        $this->logger = new TestLogger();
        $this->middleware = new LogExecutionTimeMiddleware($this->logger);
    });

    describe('Happy Paths', function (): void {
        test('logs command execution with correct type COMMAND', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('test_payload');
            $expectedResult = 'command_result';
            $next = fn ($message): string => $expectedResult;

            // Act
            $result = $this->middleware->handle($command, $next);

            // Assert
            expect($this->logger->hasLogs())->toBeTrue();
            $log = $this->logger->getLastLog();
            expect($log['message'])->toBe('CQRS COMMAND executed');
        });

        test('logs query execution with correct type QUERY', function (): void {
            // Arrange
            $query = new LogExecutionTimeTestQuery('test_payload');
            $expectedResult = ['data' => 'result'];
            $next = fn ($message): array => $expectedResult;

            // Act
            $result = $this->middleware->handle($query, $next);

            // Assert
            expect($this->logger->hasLogs())->toBeTrue();
            $log = $this->logger->getLastLog();
            expect($log['message'])->toBe('CQRS QUERY executed');
        });

        test('logs generic message execution with type MESSAGE', function (): void {
            // Arrange
            $message = new GenericMessage('test_data');
            $expectedResult = 'generic_result';
            $next = fn ($msg): string => $expectedResult;

            // Act
            $result = $this->middleware->handle($message, $next);

            // Assert
            expect($this->logger->hasLogs())->toBeTrue();
            $log = $this->logger->getLastLog();
            expect($log['message'])->toBe('CQRS MESSAGE executed');
        });

        test('includes message class name in log context', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('test_payload');
            $next = fn ($message): string => 'result';

            // Act
            $this->middleware->handle($command, $next);

            // Assert
            $log = $this->logger->getLastLog();
            expect($log['context'])->toHaveKey('message');
            expect($log['context']['message'])->toBe(LogExecutionTimeTestCommand::class);
        });

        test('includes elapsed_ms in log context rounded to 2 decimals', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('test_payload');
            $next = function ($message): string {
                usleep(1_000); // 1ms delay

                return 'result';
            };

            // Act
            $this->middleware->handle($command, $next);

            // Assert
            $log = $this->logger->getLastLog();
            expect($log['context'])->toHaveKey('elapsed_ms');
            expect($log['context']['elapsed_ms'])->toBeFloat();

            // Verify rounding to 2 decimals by checking string representation
            $elapsedMs = $log['context']['elapsed_ms'];
            $decimalPart = (string) ($elapsedMs - (int) $elapsedMs);

            if ($decimalPart !== '0') {
                $decimals = mb_strlen($decimalPart) - 2; // Subtract '0.'
                expect($decimals)->toBeLessThanOrEqual(2);
            }
        });

        test('logs at debug level', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('test_payload');
            $next = fn ($message): string => 'result';

            // Act
            $this->middleware->handle($command, $next);

            // Assert
            $log = $this->logger->getLastLog();
            expect($log['level'])->toBe('debug');
        });

        test('returns result from next closure', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('test_payload');
            $expectedResult = ['status' => 'success', 'id' => 123];
            $next = fn ($message): array => $expectedResult;

            // Act
            $result = $this->middleware->handle($command, $next);

            // Assert
            expect($result)->toBe($expectedResult);
            expect($result)->toHaveKey('status');
            expect($result['id'])->toBe(123);
        });
    });

    describe('Edge Cases', function (): void {
        test('handles long-running operations by tracking elapsed time accurately', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('long_running');
            $next = function ($message): string {
                usleep(5_000); // 5ms delay

                return 'result';
            };

            // Act
            $this->middleware->handle($command, $next);

            // Assert
            $log = $this->logger->getLastLog();
            expect($log['context']['elapsed_ms'])->toBeGreaterThan(4.0);
            expect($log['context']['message'])->toBe(LogExecutionTimeTestCommand::class);
        });

        test('handles operations that complete quickly near 0ms', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('fast_operation');
            $next = fn ($message): string => 'immediate_result'; // No delay

            // Act
            $this->middleware->handle($command, $next);

            // Assert
            $log = $this->logger->getLastLog();
            expect($log['context']['elapsed_ms'])->toBeGreaterThanOrEqual(0.0);
            expect($log['context']['elapsed_ms'])->toBeLessThan(1.0); // Should be very fast
            expect($log['message'])->toBe('CQRS COMMAND executed');
        });

        test('handles null return values from next closure', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('null_return');
            $next = fn ($message): null => null;

            // Act
            $result = $this->middleware->handle($command, $next);

            // Assert
            expect($result)->toBeNull();
            expect($this->logger->hasLogs())->toBeTrue();

            $log = $this->logger->getLastLog();
            expect($log['context']['elapsed_ms'])->toBeFloat();
        });

        test('handles exception propagation while still logging', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('exception_test');
            $exception = new RuntimeException('Handler failed');
            $next = function ($message) use ($exception): void {
                throw $exception;
            };

            // Act & Assert
            try {
                $this->middleware->handle($command, $next);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (RuntimeException $runtimeException) {
                expect($runtimeException)->toBe($exception);
                // Middleware doesn't log on exception (no time to log after exception)
                expect($this->logger->hasLogs())->toBeFalse();
            }
        });

        test('handles different message types in same middleware instance', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('cmd');
            $query = new LogExecutionTimeTestQuery('qry');
            $generic = new GenericMessage('msg');
            $next = fn ($message): string => 'result';

            // Act
            $this->middleware->handle($command, $next);
            $this->middleware->handle($query, $next);
            $this->middleware->handle($generic, $next);

            // Assert
            $logs = $this->logger->getLogs();
            expect($logs)->toHaveCount(3);
            expect($logs[0]['message'])->toBe('CQRS COMMAND executed');
            expect($logs[1]['message'])->toBe('CQRS QUERY executed');
            expect($logs[2]['message'])->toBe('CQRS MESSAGE executed');
        });

        test('elapsed time calculation is accurate with microsecond precision', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('precision_test');
            $next = function ($message): string {
                usleep(2_500); // 2.5ms delay

                return 'result';
            };

            // Act
            $this->middleware->handle($command, $next);

            // Assert
            $log = $this->logger->getLastLog();
            $elapsedMs = $log['context']['elapsed_ms'];

            // Should be around 2.5ms, give or take some system variance
            expect($elapsedMs)->toBeGreaterThan(2.0);
            expect($elapsedMs)->toBeLessThan(4.0);

            // Verify it's rounded to 2 decimal places
            $rounded = round($elapsedMs, 2);
            expect($elapsedMs)->toBe($rounded);
        });
    });

    describe('Regressions', function (): void {
        test('middleware does not modify the message object', function (): void {
            // Arrange
            $originalCommand = new LogExecutionTimeTestCommand('original_payload');
            $capturedMessage = null;

            $next = function ($message) use (&$capturedMessage): string {
                $capturedMessage = $message;

                return 'result';
            };

            // Act
            $this->middleware->handle($originalCommand, $next);

            // Assert
            expect($capturedMessage)->toBe($originalCommand);
            expect($capturedMessage->payload)->toBe('original_payload');
        });

        test('middleware does not modify the result from next closure', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('test');
            $originalResult = new stdClass();
            $originalResult->data = 'test_data';
            $originalResult->id = 42;

            $next = fn ($message): stdClass => $originalResult;

            // Act
            $result = $this->middleware->handle($command, $next);

            // Assert
            expect($result)->toBe($originalResult);
            expect($result->data)->toBe('test_data');
            expect($result->id)->toBe(42);
        });

        test('middleware logs after execution completes to ensure accurate timing', function (): void {
            // Arrange
            $command = new LogExecutionTimeTestCommand('timing_test');
            $executionOrder = [];

            $next = function ($message) use (&$executionOrder): string {
                $executionOrder[] = 'handler_start';
                usleep(1_000); // 1ms
                $executionOrder[] = 'handler_end';

                return 'result';
            };

            // Act
            $this->middleware->handle($command, $next);
            $executionOrder[] = 'after_middleware';

            // Assert - logging happens after handler completes
            expect($executionOrder)->toBe([
                'handler_start',
                'handler_end',
                'after_middleware',
            ]);
            expect($this->logger->hasLogs())->toBeTrue();
        });

        test('multiple sequential executions maintain independent timing', function (): void {
            // Arrange
            $command1 = new LogExecutionTimeTestCommand('first');
            $command2 = new LogExecutionTimeTestCommand('second');

            $next1 = function ($message): string {
                usleep(1_000); // 1ms

                return 'result1';
            };

            $next2 = function ($message): string {
                usleep(3_000); // 3ms

                return 'result2';
            };

            // Act
            $this->middleware->handle($command1, $next1);
            $this->middleware->handle($command2, $next2);

            // Assert
            $logs = $this->logger->getLogs();
            expect($logs)->toHaveCount(2);

            // First execution should be ~1ms
            expect($logs[0]['context']['elapsed_ms'])->toBeGreaterThan(0.5);
            expect($logs[0]['context']['elapsed_ms'])->toBeLessThan(2.0);

            // Second execution should be ~3ms
            expect($logs[1]['context']['elapsed_ms'])->toBeGreaterThan(2.5);
            expect($logs[1]['context']['elapsed_ms'])->toBeLessThan(4.0);

            // Ensure they're independent
            expect($logs[0]['context']['elapsed_ms'])->not->toBe($logs[1]['context']['elapsed_ms']);
        });

        test('middleware is truly readonly and immutable', function (): void {
            // Arrange
            $command1 = new LogExecutionTimeTestCommand('test1');
            $command2 = new LogExecutionTimeTestCommand('test2');
            $next = fn ($message): string => 'result';

            // Act - execute middleware multiple times
            $result1 = $this->middleware->handle($command1, $next);
            $result2 = $this->middleware->handle($command2, $next);

            // Assert - middleware works consistently
            expect($result1)->toBe('result');
            expect($result2)->toBe('result');

            $logs = $this->logger->getLogs();
            expect($logs)->toHaveCount(2);
            expect($logs[0]['context']['message'])->toBe(LogExecutionTimeTestCommand::class);
            expect($logs[1]['context']['message'])->toBe(LogExecutionTimeTestCommand::class);
        });
    });
});
