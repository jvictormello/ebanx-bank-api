<?php

namespace App\Http\Controllers;

use App\Services\BankingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BalanceController extends Controller
{
    public function __construct(
        private readonly BankingService $bankingService,
    ) {
    }

    public function show(Request $request)
    {
        $accountId = (string) $request->query('account_id', '');

        $balance = $this->bankingService->getBalance($accountId);

        if ($balance === null) {
            return response('0', Response::HTTP_NOT_FOUND)
                ->header('Content-Type', 'text/plain');
        }

        return response((string) $balance, Response::HTTP_OK)
            ->header('Content-Type', 'text/plain');
    }
}
