## Table of Contents

1. [Overview](#doc-docs-readme)
2. [Api Reference](#doc-docs-api-reference)
3. [Command Bus](#doc-docs-command-bus)
4. [Handler Discovery](#doc-docs-handler-discovery)
5. [Middleware](#doc-docs-middleware)
6. [Query Bus](#doc-docs-query-bus)
<a id="doc-docs-readme"></a>

A lightweight, extensible message bus implementation for Laravel supporting both Command and Query patterns with middleware pipeline architecture.

## Requirements

- PHP 8.5+
- Laravel 12.28+

## Installation

```bash
composer require cline/message-bus
```

The service provider is auto-discovered. Handler discovery works automatically in local development.

## Quick Start

### Command Bus

Dispatch commands for write operations:

```php
use Cline\MessageBus\Facades\CommandBus;

$result = CommandBus::dispatch(new CreateUserCommand(
    email: 'user@example.com',
    name: 'John Doe',
));
```

### Query Bus

Execute queries for read operations:

```php
use Cline\MessageBus\Facades\QueryBus;

$user = QueryBus::ask(new GetUserByEmailQuery(
    email: 'user@example.com',
));
```

## Core Concepts

### CQRS Pattern

This package implements Command Query Responsibility Segregation (CQRS):

- **Commands** - Write operations that modify state
- **Queries** - Read operations that return data

Separating reads and writes allows you to optimize each path independently.

### Pipeline Architecture

Both buses use Laravel's Pipeline to process messages through middleware:

```
Command → Middleware 1 → Middleware 2 → Handler → Result
```

Configure middleware globally via config or per-dispatch with `withMiddleware()`.

### Automatic Handler Discovery

In local development, handlers are discovered automatically via PHP attributes:

```php
use Cline\MessageBus\Commands\Attributes\AsCommandHandler;

#[AsCommandHandler(CreateUserCommand::class)]
final readonly class CreateUserHandler
{
    public function handle(CreateUserCommand $command): User
    {
        // Handle the command
    }
}
```

For production, cache the handler map for performance.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=message-bus-config
```

Config options:

```php
return [
    'paths' => [
        'command_handlers' => base_path('bootstrap/cache/command-handlers.php'),
        'query_handlers' => base_path('bootstrap/cache/query-handlers.php'),
    ],
];
```

## Handler Directory Conventions

The discovery system scans these directory patterns:

- **Legacy**: `Application/CommandHandler/` and `Application/QueryHandler/`
- **Modern**: `Application/Command/Handlers/` and `Application/Query/Handlers/`

All handler classes must be within the `Monolith\` namespace.

<a id="doc-docs-api-reference"></a>

Complete API documentation for all classes and interfaces in the message-bus package.

## CommandBus

Pipeline-based synchronous command bus.

### Methods

#### `dispatch(object $command): mixed`

Dispatch a command synchronously through the middleware pipeline.

```php
$result = $commandBus->dispatch(new CreateUserCommand(...));
```

#### `middleware(array|string|callable|object $middleware): self`

Append middleware to this bus instance. Persists across dispatches.

```php
$commandBus->middleware(LoggingMiddleware::class);
$commandBus->middleware([FirstMiddleware::class, SecondMiddleware::class]);
$commandBus->middleware(fn ($cmd, $next) => $next($cmd));
```

#### `withMiddleware(array|string|callable|object $middleware): self`

Clone the bus with extra middleware for the next dispatch only.

```php
$result = $commandBus
    ->withMiddleware(TransactionMiddleware::class)
    ->dispatch($command);
```

## QueryBus

Pipeline-based synchronous query bus.

### Methods

#### `ask(object $query): mixed`

Execute a query synchronously through the middleware pipeline.

```php
$result = $queryBus->ask(new GetUserByIdQuery(id: 123));
```

#### `middleware(array|string|callable|object $middleware): self`

Append middleware to this bus instance.

```php
$queryBus->middleware(CacheMiddleware::class);
```

#### `withMiddleware(array|string|callable|object $middleware): self`

Clone the bus with extra middleware for the next query only.

```php
$result = $queryBus
    ->withMiddleware(new LoggingMiddleware($logger))
    ->ask($query);
```

## Facades

### CommandBus Facade

```php
use Cline\MessageBus\Facades\CommandBus;

CommandBus::dispatch($command);
CommandBus::middleware($middleware);
CommandBus::withMiddleware($middleware);
```

### QueryBus Facade

```php
use Cline\MessageBus\Facades\QueryBus;

QueryBus::ask($query);
QueryBus::middleware($middleware);
QueryBus::withMiddleware($middleware);
```

## Interfaces

### CommandBusInterface

```php
namespace Cline\MessageBus\Commands\Contracts;

interface CommandBusInterface
{
    public function dispatch(object $command): mixed;

    public function middleware(array|string|callable|object $middleware): self;

    public function withMiddleware(array|string|callable|object $middleware): self;
}
```

### QueryBusInterface

```php
namespace Cline\MessageBus\Queries\Contracts;

interface QueryBusInterface
{
    public function ask(object $query): mixed;

    public function middleware(array|string|callable|object $middleware): self;

    public function withMiddleware(array|string|callable|object $middleware): self;
}
```

### BusMiddlewareInterface

```php
namespace Cline\MessageBus\Contracts;

interface BusMiddlewareInterface
{
    public function handle(object $message, Closure $next): mixed;
}
```

## Attributes

### AsCommandHandler

```php
namespace Cline\MessageBus\Commands\Attributes;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class AsCommandHandler
{
    public function __construct(
        public string $command, // class-string of the command
    ) {}
}
```

### AsQueryHandler

```php
namespace Cline\MessageBus\Queries\Attributes;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class AsQueryHandler
{
    public function __construct(
        public string $query, // class-string of the query
    ) {}
}
```

## Abstract Classes

### AbstractCommand

Base class for command DTOs:

```php
namespace Cline\MessageBus\Commands\Support;

abstract readonly class AbstractCommand {}
```

### AbstractQuery

Base class for query DTOs:

```php
namespace Cline\MessageBus\Queries\Support;

abstract readonly class AbstractQuery {}
```

## HandlerDiscovery

Static utility for discovering handlers:

```php
namespace Cline\MessageBus\Discovery;

final class HandlerDiscovery
{
    /**
     * @return array{commands: array<class-string, string>, queries: array<class-string, string>}
     */
    public static function discover(): array;
}
```

Returns associative arrays mapping message classes to handler references.

## LogExecutionTimeMiddleware

Built-in middleware for performance logging:

```php
namespace Cline\MessageBus\Middleware;

final readonly class LogExecutionTimeMiddleware implements BusMiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(object $message, Closure $next): mixed;
}
```

Logs at debug level with message class and elapsed milliseconds.

<a id="doc-docs-command-bus"></a>

The Command Bus dispatches commands for write operations through a middleware pipeline to registered handlers.

## Creating Commands

Commands are simple DTOs representing write operations:

```php
final readonly class CreateUserCommand
{
    public function __construct(
        public string $email,
        public string $name,
        public ?string $password = null,
    ) {}
}
```

Use `readonly` classes to ensure immutability. Commands should be pure data containers.

## Creating Handlers

Handlers process commands and perform the actual work:

```php
use Cline\MessageBus\Commands\Attributes\AsCommandHandler;

#[AsCommandHandler(CreateUserCommand::class)]
final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepository $repository,
        private PasswordHasher $hasher,
    ) {}

    public function handle(CreateUserCommand $command): User
    {
        return $this->repository->create([
            'email' => $command->email,
            'name' => $command->name,
            'password' => $this->hasher->hash($command->password),
        ]);
    }
}
```

The `#[AsCommandHandler]` attribute enables automatic discovery.

## Dispatching Commands

### Via Facade

```php
use Cline\MessageBus\Facades\CommandBus;

$user = CommandBus::dispatch(new CreateUserCommand(
    email: 'user@example.com',
    name: 'John Doe',
    password: 'secret123',
));
```

### Via Dependency Injection

```php
use Cline\MessageBus\Commands\Contracts\CommandBusInterface;

class UserController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function store(Request $request): Response
    {
        $user = $this->commandBus->dispatch(
            new CreateUserCommand(
                email: $request->email,
                name: $request->name,
            )
        );

        return response()->json($user);
    }
}
```

## Commands Without Return Values

Some commands don't return anything:

```php
final readonly class SendWelcomeEmailCommand
{
    public function __construct(
        public string $userId,
    ) {}
}

#[AsCommandHandler(SendWelcomeEmailCommand::class)]
final readonly class SendWelcomeEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    public function handle(SendWelcomeEmailCommand $command): void
    {
        $this->mailer->send(new WelcomeEmail($command->userId));
    }
}
```

## Command Validation

Validate inputs in the command constructor:

```php
final readonly class CreateUserCommand
{
    public function __construct(
        public string $email,
        public string $name,
    ) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }
    }
}
```

This ensures commands are always valid before reaching the handler.

## Abstract Command Base

Optionally extend the base class for type hints:

```php
use Cline\MessageBus\Commands\Support\AbstractCommand;

final readonly class CreateUserCommand extends AbstractCommand
{
    public function __construct(
        public string $email,
        public string $name,
    ) {}
}
```

## Method-Level Handlers

For handlers that process multiple commands:

```php
final readonly class UserHandler
{
    #[AsCommandHandler(CreateUserCommand::class)]
    public function handleCreate(CreateUserCommand $command): User
    {
        // Create user
    }

    #[AsCommandHandler(UpdateUserCommand::class)]
    public function handleUpdate(UpdateUserCommand $command): User
    {
        // Update user
    }
}
```

The discovery system detects both class-level and method-level attributes.

<a id="doc-docs-handler-discovery"></a>

Automatic handler discovery uses PHP attributes to map commands and queries to their handlers without manual registration.

## How Discovery Works

The discovery system:

1. Scans the Composer classmap for classes in the `Monolith\` namespace
2. Filters to handler directories (`/Application/Command/Handlers/` or `/Application/CommandHandler/`)
3. Reads `#[AsCommandHandler]` and `#[AsQueryHandler]` attributes
4. Builds a map of message classes to handler classes

## Attributes

### AsCommandHandler

Mark a class or method as a command handler:

```php
use Cline\MessageBus\Commands\Attributes\AsCommandHandler;

#[AsCommandHandler(CreateUserCommand::class)]
final readonly class CreateUserHandler
{
    public function handle(CreateUserCommand $command): User
    {
        // ...
    }
}
```

### AsQueryHandler

Mark a class or method as a query handler:

```php
use Cline\MessageBus\Queries\Attributes\AsQueryHandler;

#[AsQueryHandler(GetUserByIdQuery::class)]
final readonly class GetUserByIdHandler
{
    public function handle(GetUserByIdQuery $query): ?User
    {
        // ...
    }
}
```

## Attribute Targets

Attributes work at both class and method level:

### Class-Level (Recommended)

```php
#[AsCommandHandler(CreateUserCommand::class)]
final readonly class CreateUserHandler
{
    public function handle(CreateUserCommand $command): User
    {
        // Handler maps to: CreateUserHandler::class
    }
}
```

### Method-Level

```php
final readonly class UserCommandHandler
{
    #[AsCommandHandler(CreateUserCommand::class)]
    public function handleCreate(CreateUserCommand $command): User
    {
        // Handler maps to: UserCommandHandler::class . '@handleCreate'
    }

    #[AsCommandHandler(UpdateUserCommand::class)]
    public function handleUpdate(UpdateUserCommand $command): User
    {
        // Handler maps to: UserCommandHandler::class . '@handleUpdate'
    }
}
```

## Multiple Attributes

A single handler can handle multiple messages:

```php
#[AsCommandHandler(CreateUserCommand::class)]
#[AsCommandHandler(ImportUserCommand::class)]
final readonly class CreateUserHandler
{
    public function handle(object $command): User
    {
        // Handle both command types
    }
}
```

## Directory Conventions

Discovery scans these patterns within the `Monolith\` namespace:

### Modern Convention

```
src/
└── Application/
    ├── Command/
    │   └── Handlers/
    │       ├── CreateUserHandler.php
    │       └── UpdateUserHandler.php
    └── Query/
        └── Handlers/
            ├── GetUserByIdHandler.php
            └── ListUsersHandler.php
```

### Legacy Convention

```
src/
└── Application/
    ├── CommandHandler/
    │   ├── CreateUserCommandHandler.php
    │   └── UpdateUserCommandHandler.php
    └── QueryHandler/
        ├── GetUserByIdQueryHandler.php
        └── ListUsersQueryHandler.php
```

Both conventions are supported simultaneously for migration purposes.

## Local Development

In local development (`APP_ENV=local`), discovery runs on every request. This provides:

- Automatic registration of new handlers
- No cache management required
- Instant feedback during development

## Production Caching

For production, cache the handler map to avoid runtime reflection:

```bash
php artisan handlers:cache
```

This generates:
- `bootstrap/cache/command-handlers.php`
- `bootstrap/cache/query-handlers.php`

The service provider loads these cached maps at boot.

## Cache Configuration

Customize cache paths in `config/message-bus.php`:

```php
return [
    'paths' => [
        'command_handlers' => base_path('bootstrap/cache/command-handlers.php'),
        'query_handlers' => base_path('bootstrap/cache/query-handlers.php'),
    ],
];
```

## Clearing Cache

To clear the handler cache:

```bash
php artisan handlers:clear
```

Or delete the cache files manually.

## Excluded Classes

The discovery system automatically skips:

- Abstract classes
- Interfaces
- Classes outside `/Application/` directories
- Classes outside the `Monolith\` namespace

<a id="doc-docs-middleware"></a>

Middleware wraps command and query execution with cross-cutting concerns like logging, validation, and transactions.

## Creating Middleware

Implement the `BusMiddlewareInterface`:

```php
use Cline\MessageBus\Contracts\BusMiddlewareInterface;
use Closure;

final readonly class LoggingMiddleware implements BusMiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(object $message, Closure $next): mixed
    {
        $this->logger->info('Processing: ' . $message::class);

        $result = $next($message);

        $this->logger->info('Completed: ' . $message::class);

        return $result;
    }
}
```

## Global Middleware

Configure middleware globally via config:

```php
// config/cqrs.php
return [
    'command' => [
        'middleware' => [
            LoggingMiddleware::class,
            ValidationMiddleware::class,
        ],
    ],
    'query' => [
        'middleware' => [
            CacheMiddleware::class,
        ],
    ],
];
```

## Instance Middleware

Add middleware to a specific bus instance:

```php
use Cline\MessageBus\Facades\CommandBus;

CommandBus::middleware(new LoggingMiddleware($logger));
CommandBus::middleware(new ValidationMiddleware($validator));

CommandBus::dispatch(new CreateUserCommand(...));
```

Middleware added via `middleware()` persists across all subsequent dispatches.

## Scoped Middleware

Apply middleware for a single dispatch only:

```php
$result = CommandBus::withMiddleware(new TransactionMiddleware($db))
    ->dispatch(new CreateUserCommand(...));
```

The `withMiddleware()` method:
- Returns a cloned bus instance
- Applies middleware only to the next dispatch
- Does not affect the original bus

## Middleware Execution Order

Middleware executes in the order configured:

```php
CommandBus::middleware(new LoggingMiddleware($logger));      // 1st
CommandBus::middleware(new ValidationMiddleware($validator)); // 2nd
CommandBus::middleware(new TransactionMiddleware($db));      // 3rd

// Execution: Logging → Validation → Transaction → Handler
// Return:    Handler → Transaction → Validation → Logging
```

Full order with all types:
1. Base middleware (from config)
2. Extra middleware (from `middleware()`)
3. Scoped middleware (from `withMiddleware()`)
4. Handler

## Built-in Middleware

### LogExecutionTimeMiddleware

Logs execution duration at debug level:

```php
use Cline\MessageBus\Middleware\LogExecutionTimeMiddleware;

CommandBus::middleware(new LogExecutionTimeMiddleware($logger));
```

Output:
```
[DEBUG] CQRS COMMAND executed {"message": "App\\Commands\\CreateUserCommand", "elapsed_ms": 12.34}
```

## Common Middleware Patterns

### Transaction Middleware

```php
final readonly class TransactionMiddleware implements BusMiddlewareInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function handle(object $message, Closure $next): mixed
    {
        return $this->connection->transaction(fn () => $next($message));
    }
}
```

### Validation Middleware

```php
final readonly class ValidationMiddleware implements BusMiddlewareInterface
{
    public function __construct(
        private ValidatorFactory $validator,
    ) {}

    public function handle(object $message, Closure $next): mixed
    {
        if ($message instanceof ValidatableCommand) {
            $this->validator->validate($message->rules(), $message->data());
        }

        return $next($message);
    }
}
```

### Retry Middleware

```php
final readonly class RetryMiddleware implements BusMiddlewareInterface
{
    public function __construct(
        private int $maxAttempts = 3,
        private int $delayMs = 1000,
    ) {}

    public function handle(object $message, Closure $next): mixed
    {
        $attempts = 0;

        while (true) {
            try {
                return $next($message);
            } catch (RetryableException $e) {
                $attempts++;
                if ($attempts >= $this->maxAttempts) {
                    throw $e;
                }
                usleep($this->delayMs * 1000);
            }
        }
    }
}
```

### Conditional Middleware

```php
final readonly class ConditionalMiddleware implements BusMiddlewareInterface
{
    public function handle(object $message, Closure $next): mixed
    {
        if ($message instanceof SensitiveCommand) {
            $this->checkPermissions($message);
        }

        return $next($message);
    }
}
```

## Callable Middleware

Use closures as middleware:

```php
CommandBus::middleware(function (object $command, Closure $next): mixed {
    Log::info('Processing: ' . $command::class);
    return $next($command);
});
```

## String Middleware

Register middleware by class name (resolved via container):

```php
CommandBus::middleware(LoggingMiddleware::class);
```

<a id="doc-docs-query-bus"></a>

The Query Bus executes queries for read operations through a middleware pipeline to registered handlers.

## Creating Queries

Queries are DTOs representing read operations:

```php
final readonly class GetUserByEmailQuery
{
    public function __construct(
        public string $email,
    ) {}
}
```

## Creating Handlers

Handlers process queries and return data:

```php
use Cline\MessageBus\Queries\Attributes\AsQueryHandler;

#[AsQueryHandler(GetUserByEmailQuery::class)]
final readonly class GetUserByEmailHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    public function handle(GetUserByEmailQuery $query): ?User
    {
        return $this->repository->findByEmail($query->email);
    }
}
```

## Executing Queries

### Via Facade

```php
use Cline\MessageBus\Facades\QueryBus;

$user = QueryBus::ask(new GetUserByEmailQuery(
    email: 'user@example.com',
));
```

### Via Dependency Injection

```php
use Cline\MessageBus\Queries\Contracts\QueryBusInterface;

class UserController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function show(string $email): Response
    {
        $user = $this->queryBus->ask(
            new GetUserByEmailQuery(email: $email)
        );

        return response()->json($user);
    }
}
```

## Collection Queries

Return arrays of results:

```php
final readonly class GetActiveUsersQuery
{
    public function __construct(
        public int $limit = 10,
        public int $offset = 0,
    ) {}
}

#[AsQueryHandler(GetActiveUsersQuery::class)]
final readonly class GetActiveUsersHandler
{
    public function handle(GetActiveUsersQuery $query): array
    {
        return $this->repository->findActive(
            limit: $query->limit,
            offset: $query->offset,
        );
    }
}
```

## Paginated Queries

Return structured pagination results:

```php
final readonly class PaginatedResult
{
    public function __construct(
        public array $data,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}

final readonly class GetUsersQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}

#[AsQueryHandler(GetUsersQuery::class)]
final readonly class GetUsersHandler
{
    public function handle(GetUsersQuery $query): PaginatedResult
    {
        $offset = ($query->page - 1) * $query->perPage;

        return new PaginatedResult(
            data: $this->repository->findAll(
                limit: $query->perPage,
                offset: $offset,
            ),
            total: $this->repository->count(),
            page: $query->page,
            perPage: $query->perPage,
        );
    }
}
```

## Aggregation Queries

Return computed statistics:

```php
final readonly class UserStats
{
    public function __construct(
        public int $totalOrders,
        public float $totalSpent,
        public \DateTimeImmutable $lastOrderDate,
    ) {}
}

final readonly class GetUserStatsQuery
{
    public function __construct(
        public string $userId,
    ) {}
}

#[AsQueryHandler(GetUserStatsQuery::class)]
final readonly class GetUserStatsHandler
{
    public function handle(GetUserStatsQuery $query): UserStats
    {
        return new UserStats(
            totalOrders: $this->orderRepository->countByUser($query->userId),
            totalSpent: $this->orderRepository->sumByUser($query->userId),
            lastOrderDate: $this->orderRepository->lastOrderDate($query->userId),
        );
    }
}
```

## Abstract Query Base

Optionally extend the base class:

```php
use Cline\MessageBus\Queries\Support\AbstractQuery;

final readonly class GetUserByEmailQuery extends AbstractQuery
{
    public function __construct(
        public string $email,
    ) {}
}
```

## Method-Level Handlers

For handlers that process multiple queries:

```php
final readonly class UserQueryHandler
{
    #[AsQueryHandler(GetUserByIdQuery::class)]
    public function handleById(GetUserByIdQuery $query): ?User
    {
        return $this->repository->find($query->id);
    }

    #[AsQueryHandler(GetUserByEmailQuery::class)]
    public function handleByEmail(GetUserByEmailQuery $query): ?User
    {
        return $this->repository->findByEmail($query->email);
    }
}
```
