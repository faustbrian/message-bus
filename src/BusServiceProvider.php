<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\MessageBus;

use Cline\MessageBus\Discovery\HandlerDiscovery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

use function config;
use function file_exists;
use function is_string;
use function method_exists;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class BusServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Map handlers via Laravel Bus, preferring cached maps, falling back to local discovery in dev
        $commandMap = self::loadCachedHandlers('command-handlers') ?? $this->discoverIfLocal('command-handlers');
        $queryMap = self::loadCachedHandlers('query-handlers') ?? $this->discoverIfLocal('query-handlers');

        $map = $commandMap + $queryMap; // command map wins on key collision

        if ($map === []) {
            return;
        }

        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->map($map);
    }

    /**
     * @return null|array<string, string>
     */
    private static function loadCachedHandlers(string $type): ?array
    {
        $path = match ($type) {
            'command-handlers' => config('message-bus.paths.command_handlers'),
            'query-handlers' => config('message-bus.paths.query_handlers'),
            default => throw new InvalidArgumentException('Invalid handler type: '.$type),
        };

        if (!is_string($path)) {
            return null;
        }

        if (file_exists($path)) {
            /** @var array<string, string> $map */
            $map = require $path;

            return $map;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function discoverIfLocal(string $type): array
    {
        if (!method_exists($this->app, 'isLocal') || !$this->app->isLocal()) {
            return [];
        }

        $maps = HandlerDiscovery::discover();

        return $type === 'command-handlers' ? $maps['commands'] : $maps['queries'];
    }
}
