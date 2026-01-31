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
