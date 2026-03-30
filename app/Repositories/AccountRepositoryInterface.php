<?php

namespace App\Repositories;

interface AccountRepositoryInterface
{
    public function reset(): void;

    public function getBalance(string $accountId): ?int;

    public function deposit(string $accountId, int $amount): int;

    public function withdraw(string $accountId, int $amount): ?int;

    /**
     * @return array{
     *     origin_balance: int,
     *     destination_balance: int
     * }|null
     */
    public function transfer(string $originId, string $destinationId, int $amount): ?array;
}
