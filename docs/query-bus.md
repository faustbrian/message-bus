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
