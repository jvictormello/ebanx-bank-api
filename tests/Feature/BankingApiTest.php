<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BankingApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureRedisForTests();

        $this->post('/reset');
    }

    public function test_reset_returns_200_with_ok_plain_text_body(): void
    {
        $response = $this->post('/reset');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame('OK', $response->getContent());
    }

    public function test_banking_api_matches_the_full_challenge_contract(): void
    {
        $this->get('/balance?account_id=1234')
            ->assertNotFound()
            ->assertContent('0');

        $this->postJson('/event', [
            'type' => 'deposit',
            'destination' => '100',
            'amount' => 10,
        ])->assertCreated()
            ->assertExactJson([
                'destination' => [
                    'id' => '100',
                    'balance' => 10,
                ],
            ]);

        $this->postJson('/event', [
            'type' => 'deposit',
            'destination' => '100',
            'amount' => 10,
        ])->assertCreated()
            ->assertExactJson([
                'destination' => [
                    'id' => '100',
                    'balance' => 20,
                ],
            ]);

        $this->get('/balance?account_id=100')
            ->assertOk()
            ->assertContent('20');

        $this->postJson('/event', [
            'type' => 'withdraw',
            'origin' => '200',
            'amount' => 10,
        ])->assertNotFound()
            ->assertContent('0');

        $this->postJson('/event', [
            'type' => 'withdraw',
            'origin' => '100',
            'amount' => 5,
        ])->assertCreated()
            ->assertExactJson([
                'origin' => [
                    'id' => '100',
                    'balance' => 15,
                ],
            ]);

        $this->postJson('/event', [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 15,
        ])->assertCreated()
            ->assertExactJson([
                'origin' => [
                    'id' => '100',
                    'balance' => 0,
                ],
                'destination' => [
                    'id' => '300',
                    'balance' => 15,
                ],
            ]);

        $this->postJson('/event', [
            'type' => 'transfer',
            'origin' => '200',
            'destination' => '300',
            'amount' => 15,
        ])->assertNotFound()
            ->assertContent('0');
    }

    public function test_withdraw_returns_422_when_balance_is_insufficient(): void
    {
        $this->postJson('/event', [
            'type' => 'deposit',
            'destination' => '100',
            'amount' => 10,
        ])->assertCreated();

        $this->postJson('/event', [
            'type' => 'withdraw',
            'origin' => '100',
            'amount' => 15,
        ])->assertStatus(422)
            ->assertExactJson([
                'message' => 'Insufficient funds.',
            ]);

        $this->get('/balance?account_id=100')
            ->assertOk()
            ->assertContent('10');
    }

    public function test_transfer_returns_422_when_balance_is_insufficient(): void
    {
        $this->postJson('/event', [
            'type' => 'deposit',
            'destination' => '100',
            'amount' => 10,
        ])->assertCreated();

        $this->postJson('/event', [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 15,
        ])->assertStatus(422)
            ->assertExactJson([
                'message' => 'Insufficient funds.',
            ]);

        $this->get('/balance?account_id=100')
            ->assertOk()
            ->assertContent('10');

        $this->get('/balance?account_id=300')
            ->assertNotFound()
            ->assertContent('0');
    }

    public function test_withdraw_allows_negative_balance_within_configured_overdraft_limit(): void
    {
        $this->postJson('/event', [
            'type' => 'deposit',
            'destination' => '100',
            'amount' => 10,
            'overdraft_limit' => 20,
        ])->assertCreated();

        $this->postJson('/event', [
            'type' => 'withdraw',
            'origin' => '100',
            'amount' => 25,
        ])->assertCreated()
            ->assertExactJson([
                'origin' => [
                    'id' => '100',
                    'balance' => -15,
                ],
            ]);

        $this->get('/balance?account_id=100')
            ->assertOk()
            ->assertContent('-15');
    }

    public function test_transfer_respects_overdraft_limit_and_blocks_when_exceeded(): void
    {
        $this->postJson('/event', [
            'type' => 'deposit',
            'destination' => '100',
            'amount' => 10,
            'overdraft_limit' => 20,
        ])->assertCreated();

        $this->postJson('/event', [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 25,
        ])->assertCreated()
            ->assertExactJson([
                'origin' => [
                    'id' => '100',
                    'balance' => -15,
                ],
                'destination' => [
                    'id' => '300',
                    'balance' => 25,
                ],
            ]);

        $this->postJson('/event', [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 6,
        ])->assertStatus(422)
            ->assertExactJson([
                'message' => 'Insufficient funds.',
            ]);

        $this->get('/balance?account_id=100')
            ->assertOk()
            ->assertContent('-15');

        $this->get('/balance?account_id=300')
            ->assertOk()
            ->assertContent('25');
    }

    public function test_deposit_rejects_overdraft_limit_update_for_existing_account(): void
    {
        $this->postJson('/event', [
            'type' => 'deposit',
            'destination' => '100',
            'amount' => 10,
        ])->assertCreated();

        $this->postJson('/event', [
            'type' => 'deposit',
            'destination' => '100',
            'amount' => 5,
            'overdraft_limit' => 20,
        ])->assertStatus(422)
            ->assertExactJson([
                'message' => 'Overdraft limit can only be set when creating a new account.',
            ]);

        $this->get('/balance?account_id=100')
            ->assertOk()
            ->assertContent('10');
    }

    private function configureRedisForTests(): void
    {
        Config::set('database.redis.client', env('REDIS_CLIENT', 'phpredis'));
        Config::set('database.redis.options.prefix', env('REDIS_PREFIX', ''));
        Config::set('database.redis.default.host', env('REDIS_HOST', '127.0.0.1'));
        Config::set('database.redis.default.password', env('REDIS_PASSWORD'));
        Config::set('database.redis.default.port', (int) env('REDIS_PORT', 6379));
        Config::set('database.redis.default.database', (int) env('REDIS_DB', 0));
    }
}
