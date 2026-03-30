<?php

namespace App\Http\Controllers;

use App\Services\BankingService;
use Symfony\Component\HttpFoundation\Response;

class ResetController extends Controller
{
    public function __construct(
        private readonly BankingService $bankingService,
    ) {
    }

    public function store()
    {
        $this->bankingService->reset();

        return response('OK', Response::HTTP_OK)
            ->header('Content-Type', 'text/plain');
    }
}
