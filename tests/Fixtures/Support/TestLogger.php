<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Support;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

use function array_last;
use function count;

/**
 * Test logger implementation that captures log entries for testing.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
final class TestLogger implements LoggerInterface
{
    /** @var array<int, array{level: string, message: string, context: array<string, mixed>}> */
    private array $logs = [];

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /**
     * Get all captured log entries.
     *
     * @return array<int, array{level: string, message: string, context: array<string, mixed>}>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Get the last logged entry.
     *
     * @return null|array{level: string, message: string, context: array<string, mixed>}
     */
    public function getLastLog(): ?array
    {
        return array_last($this->logs) ?? null;
    }

    /**
     * Clear all captured log entries.
     */
    public function clear(): void
    {
        $this->logs = [];
    }

    /**
     * Check if any logs have been captured.
     */
    public function hasLogs(): bool
    {
        return $this->logs !== [];
    }

    /**
     * Get count of logged entries.
     */
    public function count(): int
    {
        return count($this->logs);
    }
}
