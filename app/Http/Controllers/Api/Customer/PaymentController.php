<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Api\ApiController;
use App\Models\Booking;
use App\Services\BookingFlowService;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class PaymentController extends ApiController
{
    public function __construct(
        private readonly BookingFlowService $bookingFlow,
        private readonly MidtransService $midtrans
    ) {
    }

    public function charge(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeCustomerBooking($request, $booking);

        $validated = $request->validate([
            'payment_channel' => ['required', Rule::in(MidtransService::CHANNELS)],
        ]);

        $booking->loadMissing(['payment', 'customer.customerProfile', 'services']);
        $payment = $booking->payment;

        abort_unless($payment, 404, 'Payment tidak ditemukan.');
        abort_if($payment->payment_type === 'pay_at_salon', 422, 'Booking ini dibayar langsung di salon.');
        abort_if($payment->status === 'paid', 422, 'Pembayaran booking ini sudah lunas.');

        $payment = $this->midtrans->expirePaymentIfOverdue($payment);
        abort_if($payment->status === 'expired', 422, 'Waktu pembayaran sudah habis. Silakan buat booking baru.');

        $channel = $validated['payment_channel'];

        if (
            $payment->status === 'pending'
            && $payment->payment_channel === $channel
            && $payment->midtrans_order_id
            && $payment->raw_response
        ) {
            return response()->json([
                'message' => 'Instruksi pembayaran masih aktif.',
                'data' => $booking->refresh()->load($this->bookingFlow->bookingRelations()),
            ]);
        }

        $response = $this->midtrans->charge($payment, $channel);
        $this->midtrans->updatePaymentFromCharge($payment, $response, $channel);

        return response()->json([
            'message' => 'Instruksi pembayaran berhasil dibuat.',
            'data' => $booking->refresh()->load($this->bookingFlow->bookingRelations()),
        ]);
    }

    public function status(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeCustomerBooking($request, $booking);

        $booking->loadMissing('payment');
        $payment = $booking->payment;

        abort_unless($payment?->midtrans_order_id, 404, 'Transaksi Midtrans belum dibuat.');

        if ($payment->status === 'expired') {
            return response()->json([
                'message' => 'Pembayaran sudah expired.',
                'data' => $booking->refresh()->load($this->bookingFlow->bookingRelations()),
            ]);
        }

        $response = $this->midtrans->status($payment->midtrans_order_id);
        $payment = $this->midtrans->updatePaymentFromStatus($payment, $response);

        if ($this->midtrans->isPaymentLocallyExpired($payment)) {
            try {
                $expireResponse = $this->midtrans->expire($payment->midtrans_order_id);
                $this->midtrans->expirePayment($payment, $expireResponse);
            } catch (Throwable) {
                try {
                    $latestResponse = $this->midtrans->status($payment->midtrans_order_id);
                    $payment = $this->midtrans->updatePaymentFromStatus($payment, $latestResponse);

                    if ($this->midtrans->isPaymentLocallyExpired($payment)) {
                        $this->midtrans->expirePayment($payment);
                    }
                } catch (Throwable) {
                    $this->midtrans->expirePayment($payment);
                }
            }
        }

        return response()->json([
            'message' => 'Status pembayaran diperbarui.',
            'data' => $booking->refresh()->load($this->bookingFlow->bookingRelations()),
        ]);
    }

    private function authorizeCustomerBooking(Request $request, Booking $booking): void
    {
        $this->authorizeRole($request, 'customer');

        abort_unless((int) $booking->customer_id === (int) $request->user()->id, 403, 'Access denied.');
    }
}
