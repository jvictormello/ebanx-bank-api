<?php

namespace App\Http\Controllers;

use App\Services\BankingService;
use App\Support\BankingErrorCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    public function __construct(
        private readonly BankingService $bankingService,
    ) {
    }

    public function store(Request $request): JsonResponse|HttpResponse
    {
        $type = (string) $request->input('type', '');

        return match ($type) {
            'deposit' => $this->handleDeposit($request),
            'withdraw' => $this->handleWithdraw($request),
            'transfer' => $this->handleTransfer($request),
            default => response('0', Response::HTTP_BAD_REQUEST)
                ->header('Content-Type', 'text/plain'),
        };
    }

    private function handleDeposit(Request $request): JsonResponse
    {
        $destinationId = (string) $request->input('destination', '');
        $amount = (int) $request->input('amount', 0);

        $result = $this->bankingService->deposit($destinationId, $amount);

        return response()->json($result, Response::HTTP_CREATED);
    }

    private function handleWithdraw(Request $request): JsonResponse|HttpResponse
    {
        $originId = (string) $request->input('origin', '');
        $amount = (int) $request->input('amount', 0);

        $result = $this->bankingService->withdraw($originId, $amount);

        if ($result === null) {
            return response('0', Response::HTTP_NOT_FOUND)
                ->header('Content-Type', 'text/plain');
        }

        if (($result['error'] ?? null) === BankingErrorCodes::INSUFFICIENT_FUNDS) {
            return response()->json([
                'message' => 'Insufficient funds.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($result, Response::HTTP_CREATED);
    }

    private function handleTransfer(Request $request): JsonResponse|HttpResponse
    {
        $originId = (string) $request->input('origin', '');
        $destinationId = (string) $request->input('destination', '');
        $amount = (int) $request->input('amount', 0);

        $result = $this->bankingService->transfer($originId, $destinationId, $amount);

        if ($result === null) {
            return response('0', Response::HTTP_NOT_FOUND)
                ->header('Content-Type', 'text/plain');
        }

        if (($result['error'] ?? null) === BankingErrorCodes::INSUFFICIENT_FUNDS) {
            return response()->json([
                'message' => 'Insufficient funds.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($result, Response::HTTP_CREATED);
    }
}
