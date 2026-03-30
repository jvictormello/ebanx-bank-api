<?php

namespace App\Services;

use App\Repositories\AccountRepositoryInterface;

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
     * }
     */
    public function deposit(string $destinationId, int $amount): array
    {
        $balance = $this->accounts->deposit($destinationId, $amount);

        return [
            'destination' => [
                'id' => $destinationId,
                'balance' => $balance,
            ],
        ];
    }

    /**
     * @return array{
     *     origin: array{id: string, balance: int}
     * }|null
     */
    public function withdraw(string $originId, int $amount): ?array
    {
        $balance = $this->accounts->withdraw($originId, $amount);

        if ($balance === null) {
            return null;
        }

        return [
            'origin' => [
                'id' => $originId,
                'balance' => $balance,
            ],
        ];
    }

    /**
     * @return array{
     *     origin: array{id: string, balance: int},
     *     destination: array{id: string, balance: int}
     * }|null
     */
    public function transfer(string $originId, string $destinationId, int $amount): ?array
    {
        $balances = $this->accounts->transfer($originId, $destinationId, $amount);

        if ($balances === null) {
            return null;
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
