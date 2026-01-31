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
