<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Application\Command\Handlers\NonMonolithHandler;
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

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Mock classmap with all test fixtures for comprehensive testing
// Note: Paths must be absolute and will be resolved relative to the tests directory
$fixturesDir = dirname(__DIR__, 2).'/Fixtures/Discovery';

return [
    // Legacy structure - should be discovered
    CreateUserCommandHandler::class => $fixturesDir.'/Legacy/CommandHandler/CreateUserCommandHandler.php',
    GetUserQueryHandler::class => $fixturesDir.'/Legacy/QueryHandler/GetUserQueryHandler.php',
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
    // Non-handler classes in Monolith namespace but outside Application
    'Monolith\\Domain\\User' => '/some/other/path/User.php',
    'Monolith\\Infrastructure\\Database\\Connection' => '/some/other/path/Connection.php',
    // Package classes that should be ignored
    'Vendor\\Package\\SomeClass' => '/vendor/package/src/SomeClass.php',
    'Laravel\\Framework\\Controller' => '/vendor/laravel/framework/src/Controller.php',
];
