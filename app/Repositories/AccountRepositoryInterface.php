<?php

namespace App\Repositories;

interface AccountRepositoryInterface
{
    public function reset(): void;

    public function getBalance(string $accountId): ?int;

    /**
     * @return array{balance: int}|array{error: string}
     */
    public function deposit(string $accountId, int $amount, ?int $overdraftLimit = null): array;

    /**
     * @return array{balance: int}|array{error: string}|null
     */
    public function withdraw(string $accountId, int $amount): ?array;

    /**
     * @return array{
     *     origin_balance: int,
     *     destination_balance: int
     * }|array{error: string}|null
     */
    public function transfer(string $originId, string $destinationId, int $amount): ?array;
}
