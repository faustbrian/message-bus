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
