<?php

namespace App\Repositories;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;

class RedisAccountRepository implements AccountRepositoryInterface
{
    private const ACCOUNTS_HASH_KEY = 'accounts';

    public function __construct(
        private readonly RedisFactory $redis,
    ) {
    }

    public function reset(): void
    {
    }

    public function getBalance(string $accountId): ?int
    {
        return null;
    }

    public function deposit(string $accountId, int $amount): int
    {
        return 0;
    }

    public function withdraw(string $accountId, int $amount): ?int
    {
        return null;
    }

    public function transfer(string $originId, string $destinationId, int $amount): ?array
    {
        return null;
    }

    private function connection(): Connection
    {
        return $this->redis->connection();
    }
}
