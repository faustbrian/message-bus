<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit;

use App\Application\Command\Handlers\NonMonolithHandler;
use Cline\MessageBus\Discovery\HandlerDiscovery;
use Illuminate\Support\Facades\File;
use Monolith\Infrastructure\Handlers\NonApplicationHandler;
use Monolith\Legacy\Application\CommandHandler\CreateUserCommandHandler;
use Monolith\Legacy\Application\QueryHandler\GetUserQueryHandler;
use Monolith\Modern\Application\Command\Handlers\AbstractCommandHandler;
use Monolith\Modern\Application\Command\Handlers\CommandHandlerInterface;
use Monolith\Modern\Application\Command\Handlers\MethodLevelCommandHandler;
use Monolith\Modern\Application\Command\Handlers\MultipleAttributesHandler;
use Monolith\Modern\Application\Command\Handlers\UpdateUserHandler;
use Monolith\Modern\Application\Query\Handlers\ListUsersHandler;
use Monolith\Modern\Application\Query\Handlers\MethodLevelQueryHandler;
use Monolith\Modern\Application\Services\NotInHandlerDirectory;

use function array_merge;
use function array_values;
use function base_path;
use function beforeEach;
use function describe;
use function dirname;
use function expect;
use function explode;
use function sprintf;
use function str_contains;
use function test;
use function var_export;

/**
 * Setup a mock classmap file for testing.
 */
function setupClassmap(string $type = 'full'): void
{
    $classmapPath = base_path('vendor/composer/autoload_classmap.php');
    $fixturesDir = __DIR__.'/../Fixtures/Discovery';

    // Ensure directory exists
    File::ensureDirectoryExists(dirname($classmapPath));

    // Generate classmap with correct paths
    if ($type === 'empty') {
        $classmap = [];
    } else {
        $classmap = [
            // Legacy structure - should be discovered
            CreateUserCommandHandler::class => $fixturesDir.'/Legacy/Application/CommandHandler/CreateUserCommandHandler.php',
            GetUserQueryHandler::class => $fixturesDir.'/Legacy/Application/QueryHandler/GetUserQueryHandler.php',
            // New structure - should be discovered
            UpdateUserHandler::class => $fixturesDir.'/New/Application/Command/Handlers/UpdateUserHandler.php',
            ListUsersHandler::class => $fixturesDir.'/New/Application/Query/Handlers/ListUsersHandler.php',
            // Method-level handlers - should be discovered
            MethodLevelCommandHandler::class => $fixturesDir.'/New/Application/Command/Handlers/MethodLevelCommandHandler.php',
            MethodLevelQueryHandler::class => $fixturesDir.'/New/Application/Query/Handlers/MethodLevelQueryHandler.php',
            // Edge cases - should be skipped
            AbstractCommandHandler::class => $fixturesDir.'/New/Application/Command/Handlers/AbstractCommandHandler.php',
            CommandHandlerInterface::class => $fixturesDir.'/New/Application/Command/Handlers/CommandHandlerInterface.php',
            // Invalid - should be skipped (wrong namespace)
            NonMonolithHandler::class => $fixturesDir.'/Invalid/NonMonolithHandler.php',
            // Invalid - should be skipped (not in Application directory)
            NonApplicationHandler::class => $fixturesDir.'/Invalid/NonApplicationHandler.php',
            // Multiple attributes on same class
            MultipleAttributesHandler::class => $fixturesDir.'/New/Application/Command/Handlers/MultipleAttributesHandler.php',
            // Edge case: in /Application/ but NOT in handler directories (line 68)
            NotInHandlerDirectory::class => $fixturesDir.'/New/Application/Services/NotInHandlerDirectory.php',
            // Non-handler classes in Monolith namespace but outside Application
            'Monolith\\Domain\\User' => '/some/other/path/User.php',
            'Monolith\\Infrastructure\\Database\\Connection' => '/some/other/path/Connection.php',
            // Package classes that should be ignored
            'Vendor\\Package\\SomeClass' => '/vendor/package/src/SomeClass.php',
            'Laravel\\Framework\\Controller' => '/vendor/laravel/framework/src/Controller.php',
        ];
    }

    // Write the classmap
    $content = "<?php\nreturn ".var_export($classmap, true).";\n";
    File::put($classmapPath, $content);
}

describe('HandlerDiscovery', function (): void {
    beforeEach(function (): void {
        // Clean up any existing test classmap
        $classmapPath = base_path('vendor/composer/autoload_classmap.php');

        if (!File::exists($classmapPath)) {
            return;
        }

        File::delete($classmapPath);
    });

    describe('Happy Paths', function (): void {
        test('discovers class-level command handlers in legacy directory structure', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert
            expect($result)->toHaveKey('commands');
            expect($result)->toHaveKey('queries');
            expect($result['commands'])->toHaveKey('Monolith\\Commands\\CreateUserCommand');
            expect($result['commands']['Monolith\\Commands\\CreateUserCommand'])->toBe(CreateUserCommandHandler::class);
        });

        test('discovers class-level query handlers in legacy directory structure', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert
            expect($result['queries'])->toHaveKey('Monolith\\Queries\\GetUserQuery');
            expect($result['queries']['Monolith\\Queries\\GetUserQuery'])->toBe(GetUserQueryHandler::class);
        });

        test('discovers class-level command handlers in new directory structure', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert
            expect($result['commands'])->toHaveKey('Monolith\\Commands\\UpdateUserCommand');
            expect($result['commands']['Monolith\\Commands\\UpdateUserCommand'])->toBe(UpdateUserHandler::class);
        });

        test('discovers class-level query handlers in new directory structure', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert
            expect($result['queries'])->toHaveKey('Monolith\\Queries\\ListUsersQuery');
            expect($result['queries']['Monolith\\Queries\\ListUsersQuery'])->toBe(ListUsersHandler::class);
        });

        test('discovers method-level command handlers', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert
            expect($result['commands'])->toHaveKey('Monolith\\Commands\\DeleteUserCommand');
            expect($result['commands']['Monolith\\Commands\\DeleteUserCommand'])->toBe('Monolith\\Modern\\Application\\Command\\Handlers\\MethodLevelCommandHandler@handleDeleteUser');

            expect($result['commands'])->toHaveKey('Monolith\\Commands\\ArchiveUserCommand');
            expect($result['commands']['Monolith\\Commands\\ArchiveUserCommand'])->toBe('Monolith\\Modern\\Application\\Command\\Handlers\\MethodLevelCommandHandler@handleArchiveUser');
        });

        test('discovers method-level query handlers', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert
            expect($result['queries'])->toHaveKey('Monolith\\Queries\\FindUserQuery');
            expect($result['queries']['Monolith\\Queries\\FindUserQuery'])->toBe('Monolith\\Modern\\Application\\Query\\Handlers\\MethodLevelQueryHandler@handleFindUser');

            expect($result['queries'])->toHaveKey('Monolith\\Queries\\SearchUsersQuery');
            expect($result['queries']['Monolith\\Queries\\SearchUsersQuery'])->toBe('Monolith\\Modern\\Application\\Query\\Handlers\\MethodLevelQueryHandler@handleSearchUsers');
        });

        test('returns empty arrays when classmap does not exist', function (): void {
            // Arrange - no classmap setup

            // Act
            $result = HandlerDiscovery::discover();

            // Assert
            expect($result)->toBe([
                'commands' => [],
                'queries' => [],
            ]);
        });

        test('supports legacy directory structure with CommandHandler path', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - legacy command handler path includes /CommandHandler/
            expect($result['commands'])->toHaveKey('Monolith\\Commands\\CreateUserCommand');
            expect($result['commands']['Monolith\\Commands\\CreateUserCommand'])->toContain('CommandHandler');
        });

        test('supports legacy directory structure with QueryHandler path', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - legacy query handler path includes /QueryHandler/
            expect($result['queries'])->toHaveKey('Monolith\\Queries\\GetUserQuery');
            expect($result['queries']['Monolith\\Queries\\GetUserQuery'])->toContain('QueryHandler');
        });

        test('supports new directory structure with Application/Command/Handlers path', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - new command handler path includes /Application/Command/Handlers/
            expect($result['commands'])->toHaveKey('Monolith\\Commands\\UpdateUserCommand');
            expect($result['commands']['Monolith\\Commands\\UpdateUserCommand'])->toContain('Command');
            expect($result['commands']['Monolith\\Commands\\UpdateUserCommand'])->toContain('Handlers');
        });

        test('supports new directory structure with Application/Query/Handlers path', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - new query handler path includes /Application/Query/Handlers/
            expect($result['queries'])->toHaveKey('Monolith\\Queries\\ListUsersQuery');
            expect($result['queries']['Monolith\\Queries\\ListUsersQuery'])->toContain('Query');
            expect($result['queries']['Monolith\\Queries\\ListUsersQuery'])->toContain('Handlers');
        });
    });

    describe('Sad Paths', function (): void {
        test('skips classes not starting with Monolith namespace', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - App\Application\Command\Handlers\NonMonolithHandler should not be discovered
            expect($result['commands'])->not->toHaveKey('App\\Commands\\NonMonolithCommand');
        });

        test('skips classes not in Application path', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - Monolith\Infrastructure\Handlers\NonApplicationHandler should not be discovered
            expect($result['commands'])->not->toHaveKey('Monolith\\Commands\\InfrastructureCommand');
        });

        test('skips Monolith classes outside Application directory', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - Domain and Infrastructure classes should not be discovered
            $allHandlers = array_merge(
                array_values($result['commands']),
                array_values($result['queries']),
            );

            foreach ($allHandlers as $handler) {
                expect($handler)->not->toContain('Domain');
                expect($handler)->not->toContain('Infrastructure');
            }
        });

        test('skips classes in Application path but not in handler directories', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - NotInHandlerDirectory is in /Application/Services/ not /CommandHandler/ or /QueryHandler/
            $allHandlers = array_merge(
                array_values($result['commands']),
                array_values($result['queries']),
            );

            foreach ($allHandlers as $handler) {
                expect($handler)->not->toContain('NotInHandlerDirectory');
            }
        });
    });

    describe('Edge Cases', function (): void {
        test('skips abstract classes', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - AbstractCommandHandler should not be discovered
            expect($result['commands'])->not->toHaveKey('Monolith\\Commands\\AbstractCommand');
        });

        test('skips interfaces', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - CommandHandlerInterface should not be discovered
            expect($result['commands'])->not->toHaveKey('Monolith\\Commands\\InterfaceCommand');
        });

        test('handles multiple attributes on same class', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - MultipleAttributesHandler should register both commands
            expect($result['commands'])->toHaveKey('Monolith\\Commands\\FirstCommand');
            expect($result['commands'])->toHaveKey('Monolith\\Commands\\SecondCommand');
            expect($result['commands']['Monolith\\Commands\\FirstCommand'])->toBe(MultipleAttributesHandler::class);
            expect($result['commands']['Monolith\\Commands\\SecondCommand'])->toBe(MultipleAttributesHandler::class);
        });

        test('handles empty classmap file', function (): void {
            // Arrange
            setupClassmap('empty');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert
            expect($result)->toBe([
                'commands' => [],
                'queries' => [],
            ]);
        });

        test('skips vendor package classes', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - Vendor and Laravel classes should not be discovered
            $allHandlers = array_merge(
                array_values($result['commands']),
                array_values($result['queries']),
            );

            foreach ($allHandlers as $handler) {
                expect($handler)->not->toContain('Vendor');
                expect($handler)->not->toContain('Laravel');
            }
        });
    });

    describe('Regressions', function (): void {
        test('only processes application handler directories', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - Only handlers in CommandHandler, QueryHandler, Command/Handlers, or Query/Handlers
            $allHandlers = array_merge(
                array_values($result['commands']),
                array_values($result['queries']),
            );

            foreach ($allHandlers as $handler) {
                $handlerClass = explode('@', $handler)[0]; // Remove method name if present

                $hasLegacyPath = str_contains($handlerClass, 'CommandHandler')
                    || str_contains($handlerClass, 'QueryHandler');

                $hasNewPath = (str_contains($handlerClass, 'Command') && str_contains($handlerClass, 'Handlers'))
                    || (str_contains($handlerClass, 'Query') && str_contains($handlerClass, 'Handlers'));

                expect($hasLegacyPath || $hasNewPath)->toBeTrue(
                    sprintf('Handler %s should be in a valid handler directory', $handler),
                );
            }
        });

        test('discovers all valid handlers in single pass', function (): void {
            // Arrange
            setupClassmap('full');

            // Act
            $result = HandlerDiscovery::discover();

            // Assert - Should discover all 6 command types and 4 query types
            expect($result['commands'])->toHaveCount(6); // CreateUser, UpdateUser, DeleteUser, ArchiveUser, FirstCommand, SecondCommand
            expect($result['queries'])->toHaveCount(4);  // GetUser, ListUsers, FindUser, SearchUsers
        });
    });
});
