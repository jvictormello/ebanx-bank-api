<?php

namespace App\Repositories;

use App\Support\BankingErrorCodes;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;

class RedisAccountRepository implements AccountRepositoryInterface
{
    private const ACCOUNTS_HASH_KEY = 'accounts';
    private const MISSING_ACCOUNT = 'missing_account';

    public function __construct(
        private readonly RedisFactory $redis,
    ) {
    }

    public function reset(): void
    {
        $this->connection()->del(self::ACCOUNTS_HASH_KEY);
    }

    public function getBalance(string $accountId): ?int
    {
        $balance = $this->connection()->hget(self::ACCOUNTS_HASH_KEY, $accountId);

        if ($balance === null || $balance === false) {
            return null;
        }

        return (int) $balance;
    }

    public function deposit(string $accountId, int $amount): int
    {
        return (int) $this->connection()->hincrby(self::ACCOUNTS_HASH_KEY, $accountId, $amount);
    }

    public function withdraw(string $accountId, int $amount): ?array
    {
        $result = $this->connection()->eval(
            $this->withdrawScript(),
            1,
            self::ACCOUNTS_HASH_KEY,
            $accountId,
            (string) $amount
        );

        if ($result === null || $result === false || $result === self::MISSING_ACCOUNT) {
            return null;
        }

        if ($result === BankingErrorCodes::INSUFFICIENT_FUNDS) {
            return ['error' => BankingErrorCodes::INSUFFICIENT_FUNDS];
        }

        return ['balance' => (int) $result];
    }

    public function transfer(string $originId, string $destinationId, int $amount): ?array
    {
        $result = $this->connection()->eval(
            $this->transferScript(),
            1,
            self::ACCOUNTS_HASH_KEY,
            $originId,
            $destinationId,
            (string) $amount
        );

        if ($result === null || $result === false || $result === self::MISSING_ACCOUNT) {
            return null;
        }

        if ($result === BankingErrorCodes::INSUFFICIENT_FUNDS) {
            return ['error' => BankingErrorCodes::INSUFFICIENT_FUNDS];
        }

        return [
            'origin_balance' => (int) $result[0],
            'destination_balance' => (int) $result[1],
        ];
    }

    private function connection(): Connection
    {
        return $this->redis->connection();
    }

    private function withdrawScript(): string
    {
        return sprintf(
            <<<'LUA'
local hash = KEYS[1]
local accountId = ARGV[1]
local amount = tonumber(ARGV[2])

if redis.call('HEXISTS', hash, accountId) == 0 then
    return '%s'
end

local balance = tonumber(redis.call('HGET', hash, accountId))

if balance < amount then
    return '%s'
end

return redis.call('HINCRBY', hash, accountId, -amount)
LUA,
            self::MISSING_ACCOUNT,
            BankingErrorCodes::INSUFFICIENT_FUNDS
        );
    }

    private function transferScript(): string
    {
        return sprintf(
            <<<'LUA'
local hash = KEYS[1]
local originId = ARGV[1]
local destinationId = ARGV[2]
local amount = tonumber(ARGV[3])

if redis.call('HEXISTS', hash, originId) == 0 then
    return '%s'
end

local originBalance = tonumber(redis.call('HGET', hash, originId))

if originBalance < amount then
    return '%s'
end

originBalance = redis.call('HINCRBY', hash, originId, -amount)
local destinationBalance = redis.call('HINCRBY', hash, destinationId, amount)

return {originBalance, destinationBalance}
LUA,
            self::MISSING_ACCOUNT,
            BankingErrorCodes::INSUFFICIENT_FUNDS
        );
    }
}
