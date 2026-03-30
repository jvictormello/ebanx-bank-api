<?php

namespace App\Services;

use App\Repositories\AccountRepositoryInterface;
use App\Support\BankingErrorCodes;

class BankingService
{
    public function __construct(
        private readonly AccountRepositoryInterface $accounts,
    ) {
    }

    public function reset(): void
    {
        $this->accounts->reset();
    }

    public function getBalance(string $accountId): ?int
    {
        return $this->accounts->getBalance($accountId);
    }

    /**
     * @return array{
     *     destination: array{id: string, balance: int}
     * }|array{
     *     error: string
     * }
     */
    public function deposit(string $destinationId, int $amount, ?int $overdraftLimit = null): array
    {
        $result = $this->accounts->deposit($destinationId, $amount, $overdraftLimit);

        if (isset($result['error'])) {
            return ['error' => BankingErrorCodes::OVERDRAFT_LIMIT_ONLY_ON_CREATION];
        }

        return [
            'destination' => [
                'id' => $destinationId,
                'balance' => $result['balance'],
            ],
        ];
    }

    /**
     * @return array{
     *     origin: array{id: string, balance: int}
     * }|array{
     *     error: string
     * }|null
     */
    public function withdraw(string $originId, int $amount): ?array
    {
        $result = $this->accounts->withdraw($originId, $amount);

        if ($result === null) {
            return null;
        }

        if (isset($result['error'])) {
            return ['error' => BankingErrorCodes::INSUFFICIENT_FUNDS];
        }

        return [
            'origin' => [
                'id' => $originId,
                'balance' => $result['balance'],
            ],
        ];
    }

    /**
     * @return array{
     *     origin: array{id: string, balance: int},
     *     destination: array{id: string, balance: int}
     * }|array{
     *     error: string
     * }|null
     */
    public function transfer(string $originId, string $destinationId, int $amount): ?array
    {
        $balances = $this->accounts->transfer($originId, $destinationId, $amount);

        if ($balances === null) {
            return null;
        }

        if (isset($balances['error'])) {
            return ['error' => BankingErrorCodes::INSUFFICIENT_FUNDS];
        }

        return [
            'origin' => [
                'id' => $originId,
                'balance' => $balances['origin_balance'],
            ],
            'destination' => [
                'id' => $destinationId,
                'balance' => $balances['destination_balance'],
            ],
        ];
    }
}
