<?php

namespace App\Providers;

use App\Repositories\AccountRepositoryInterface;
use App\Repositories\RedisAccountRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, RedisAccountRepository::class);
    }

    public function boot(): void
    {
    }
}
