<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransNotificationController extends ApiController
{
    public function __construct(private readonly MidtransService $midtrans)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        abort_unless($this->midtrans->verifySignature($payload), 403, 'Invalid Midtrans signature.');

        $payment = Payment::query()
            ->where('midtrans_order_id', $payload['order_id'] ?? null)
            ->first();

        abort_unless($payment, 404, 'Payment not found.');

        $this->midtrans->updatePaymentFromStatus($payment, $payload, true);

        return response()->json(['message' => 'OK']);
    }
}
