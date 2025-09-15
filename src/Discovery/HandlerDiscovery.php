<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\MessageBus\Discovery;

use Cline\MessageBus\Attribute\AsCommandHandler;
use Cline\MessageBus\Attribute\AsQueryHandler;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

use function base_path;
use function file_exists;
use function str_contains;
use function str_starts_with;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class HandlerDiscovery
{
    /**
     * Discover handlers annotated with AsCommandHandler/AsQueryHandler.
     *
     * @return array{commands: array<class-string, string>, queries: array<class-string, string>}
     */
    public static function discover(): array
    {
        $commands = [];
        $queries = [];

        $classmapPath = base_path('vendor/composer/autoload_classmap.php');

        if (!file_exists($classmapPath)) {
            return ['commands' => [], 'queries' => []];
        }

        /** @var array<class-string, string> $classmap */
        $classmap = require $classmapPath;

        foreach ($classmap as $class => $_file) {
            // Only scan application classes
            if (!str_starts_with($class, 'Monolith\\')) {
                continue;
            }

            // Limit scope to application handler directories to avoid reflecting unrelated infrastructure
            $path = (string) $_file;

            if (!str_contains($path, '/Application/')) {
                continue;
            }

            // Accept both legacy and new directory conventions during migration:
            // - Legacy: Application/CommandHandler and Application/QueryHandler
            // - New:    Application/Command/Handlers and Application/Query/Handlers
            $inLegacyLocation = str_contains($path, '/CommandHandler/') || str_contains($path, '/QueryHandler/');
            $inNewLocation = str_contains($path, '/Application/Command/Handlers/') || str_contains($path, '/Application/Query/Handlers/');

            if (!$inLegacyLocation && !$inNewLocation) {
                continue;
            }

            try {
                $ref = new ReflectionClass($class);
            } catch (Throwable) {
                // Skip classes that cannot be reflected (missing parents, syntax errors, etc.)
                continue;
            }

            if ($ref->isAbstract() || $ref->isInterface()) {
                continue;
            }

            // Query handlers - class level
            foreach ($ref->getAttributes(AsQueryHandler::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                /** @var AsQueryHandler $instance */
                $instance = $attr->newInstance();
                $queries[$instance->query] = $class;
            }

            // Command handlers - class level
            foreach ($ref->getAttributes(AsCommandHandler::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                /** @var AsCommandHandler $instance */
                $instance = $attr->newInstance();
                $commands[$instance->command] = $class;
            }

            // Query handlers - method level
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(AsQueryHandler::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                    /** @var AsQueryHandler $instance */
                    $instance = $attr->newInstance();
                    $queries[$instance->query] = $class.'@'.$method->getName();
                }
            }

            // Command handlers - method level
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(AsCommandHandler::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                    /** @var AsCommandHandler $instance */
                    $instance = $attr->newInstance();
                    $commands[$instance->command] = $class.'@'.$method->getName();
                }
            }
        }

        return [
            'commands' => $commands,
            'queries' => $queries,
        ];
    }
}
