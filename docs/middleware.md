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
