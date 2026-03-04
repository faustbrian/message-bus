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
