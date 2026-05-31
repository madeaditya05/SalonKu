<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MidtransService
{
    public const PAYMENT_EXPIRY_MINUTES = 7;

    public const CHANNELS = [
        'qris',
        'bca_va',
        'bni_va',
        'bri_va',
        'permata_va',
        'cimb_va',
        'mandiri_bill',
    ];

    public function charge(Payment $payment, string $channel): array
    {
        $serverKey = $this->serverKey();
        $payload = $this->chargePayload($payment, $channel);

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post($this->baseUrl() . '/v2/charge', $payload);

        $data = $response->json() ?: [];

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'payment' => $data['status_message'] ?? 'Transaksi Midtrans gagal dibuat.',
            ]);
        }

        return $data;
    }

    public function status(string $orderId): array
    {
        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->timeout(20)
            ->get($this->baseUrl() . '/v2/' . rawurlencode($orderId) . '/status');

        $data = $response->json() ?: [];

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'payment' => $data['status_message'] ?? 'Status pembayaran belum bisa dicek.',
            ]);
        }

        return $data;
    }

    public function expire(string $orderId): array
    {
        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->timeout(20)
            ->post($this->baseUrl() . '/v2/' . rawurlencode($orderId) . '/expire');

        $data = $response->json() ?: [];

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'payment' => $data['status_message'] ?? 'Transaksi Midtrans belum bisa di-expire.',
            ]);
        }

        return $data;
    }

    public function displayFields(array $response, string $channel): array
    {
        $actions = collect($response['actions'] ?? []);
        $qrAction = $actions->firstWhere('name', 'generate-qr-code');
        $deeplinkAction = $actions->firstWhere('name', 'deeplink-redirect');
        $qrUrl = is_array($qrAction) ? ($qrAction['url'] ?? null) : null;
        $deeplinkUrl = is_array($deeplinkAction) ? ($deeplinkAction['url'] ?? null) : null;
        $codeLabel = null;
        $paymentCode = null;
        $billerCode = null;

        if (! empty($response['va_numbers'][0])) {
            $bank = strtoupper((string) ($response['va_numbers'][0]['bank'] ?? 'VA'));
            $codeLabel = "{$bank} Virtual Account";
            $paymentCode = $response['va_numbers'][0]['va_number'] ?? null;
        }

        if (! empty($response['permata_va_number'])) {
            $codeLabel = 'Permata Virtual Account';
            $paymentCode = $response['permata_va_number'];
        }

        if (! empty($response['bill_key']) || ! empty($response['biller_code'])) {
            $codeLabel = 'Mandiri Bill Key';
            $paymentCode = $response['bill_key'] ?? null;
            $billerCode = $response['biller_code'] ?? null;
        }

        if ($channel === 'qris') {
            $codeLabel = 'QRIS';
        }

        return [
            'midtrans_order_id' => $response['order_id'] ?? null,
            'midtrans_transaction_id' => $response['transaction_id'] ?? null,
            'midtrans_transaction_status' => $response['transaction_status'] ?? null,
            'fraud_status' => $response['fraud_status'] ?? null,
            'payment_code_label' => $codeLabel,
            'payment_code' => $paymentCode,
            'biller_code' => $billerCode,
            'qr_url' => $qrUrl,
            'deeplink_url' => $deeplinkUrl,
            'expiry_time' => $this->parseMidtransTime($response['expiry_time'] ?? null)
                ?: now()->addMinutes(self::PAYMENT_EXPIRY_MINUTES),
            'raw_response' => $response,
        ];
    }

    public function updatePaymentFromCharge(Payment $payment, array $response, string $channel): Payment
    {
        $status = $this->paymentStatus(
            $response['transaction_status'] ?? null,
            $response['fraud_status'] ?? null
        );

        return DB::transaction(function () use ($payment, $response, $channel, $status) {
            $payment->update(array_merge($this->displayFields($response, $channel), [
                'payment_method' => 'midtrans',
                'payment_channel' => $channel,
                'status' => $status,
                'paid_at' => $status === 'paid' ? ($payment->paid_at ?: now()) : null,
            ]));

            $this->updateBookingPaymentState($payment, $status);

            return $payment->refresh();
        });
    }

    public function updatePaymentFromStatus(Payment $payment, array $response, bool $notification = false): Payment
    {
        $status = $this->paymentStatus(
            $response['transaction_status'] ?? null,
            $response['fraud_status'] ?? null
        );

        return DB::transaction(function () use ($payment, $response, $notification, $status) {
            $updates = [
                'status' => $status,
                'midtrans_transaction_status' => $response['transaction_status'] ?? $payment->midtrans_transaction_status,
                'fraud_status' => $response['fraud_status'] ?? $payment->fraud_status,
                'paid_at' => $status === 'paid' ? ($payment->paid_at ?: now()) : $payment->paid_at,
            ];

            if (! empty($response['expiry_time'])) {
                $updates['expiry_time'] = $this->parseMidtransTime($response['expiry_time']);
            }

            if (! empty($response['transaction_id'])) {
                $updates['midtrans_transaction_id'] = $response['transaction_id'];
            }

            if ($notification) {
                $updates['raw_notification'] = $response;
            }

            $payment->update($updates);
            $this->updateBookingPaymentState($payment, $status);

            return $payment->refresh();
        });
    }

    public function isPaymentLocallyExpired(Payment $payment): bool
    {
        return in_array($payment->status, ['unpaid', 'pending'], true)
            && $payment->expiry_time
            && $payment->expiry_time->lte(now());
    }

    public function expirePayment(Payment $payment, ?array $response = null, bool $notification = false): Payment
    {
        return DB::transaction(function () use ($payment, $response, $notification) {
            $updates = [
                'status' => 'expired',
                'midtrans_transaction_status' => $response['transaction_status'] ?? 'expire',
                'fraud_status' => $response['fraud_status'] ?? $payment->fraud_status,
            ];

            if (! empty($response['transaction_id'])) {
                $updates['midtrans_transaction_id'] = $response['transaction_id'];
            }

            if (! empty($response['expiry_time'])) {
                $updates['expiry_time'] = $this->parseMidtransTime($response['expiry_time']) ?: $payment->expiry_time;
            }

            if ($notification && $response) {
                $updates['raw_notification'] = $response;
            }

            $payment->update($updates);
            $this->updateBookingPaymentState($payment, 'expired');

            return $payment->refresh();
        });
    }

    public function expirePaymentIfOverdue(Payment $payment): Payment
    {
        return $this->isPaymentLocallyExpired($payment)
            ? $this->expirePayment($payment)
            : $payment;
    }

    public function expireOverduePaymentsForCustomer(int $customerId): int
    {
        $payments = Payment::query()
            ->whereIn('status', ['unpaid', 'pending'])
            ->whereNotNull('expiry_time')
            ->where('expiry_time', '<=', now())
            ->whereHas('booking', fn ($query) => $query->where('customer_id', $customerId))
            ->with('booking')
            ->get();

        $payments->each(fn (Payment $payment) => $this->expirePayment($payment));

        return $payments->count();
    }

    public function paymentStatus(?string $transactionStatus = null, ?string $fraudStatus = null): string
    {
        return match ($transactionStatus) {
            'settlement' => 'paid',
            'capture' => $fraudStatus === 'challenge' ? 'pending' : 'paid',
            'pending' => 'pending',
            'refund', 'partial_refund' => 'refunded',
            'expire' => 'expired',
            'cancel', 'deny', 'failure' => 'failed',
            default => 'pending',
        };
    }

    public function verifySignature(array $payload): bool
    {
        $signature = (string) ($payload['signature_key'] ?? '');

        if ($signature === '') {
            return false;
        }

        $source = ($payload['order_id'] ?? '')
            . ($payload['status_code'] ?? '')
            . ($payload['gross_amount'] ?? '')
            . $this->serverKey();

        return hash_equals(hash('sha512', $source), $signature);
    }

    private function updateBookingPaymentState(Payment $payment, string $paymentStatus): void
    {
        $payment->loadMissing('booking');
        $booking = $payment->booking;

        if (! $booking) {
            return;
        }

        $updates = ['payment_status' => $paymentStatus];

        if ($payment->payment_type !== 'pay_at_salon') {
            if ($paymentStatus === 'pending') {
                $updates['status'] = 'pending_payment';
            }

            if ($paymentStatus === 'paid' && $booking->status === 'pending_payment') {
                $updates['status'] = in_array($booking->booking_type, ['queue', 'walk_in'], true)
                    ? 'waiting'
                    : 'confirmed';
            }
        }

        $booking->update($updates);
    }

    private function chargePayload(Payment $payment, string $channel): array
    {
        if (! in_array($channel, self::CHANNELS, true)) {
            throw ValidationException::withMessages([
                'payment_channel' => 'Metode pembayaran tidak tersedia.',
            ]);
        }

        $payment->loadMissing('booking.customer.customerProfile', 'booking.services');
        $booking = $payment->booking;
        $grossAmount = (int) round((float) $payment->amount);

        if ($grossAmount < 1 || ! $booking) {
            throw ValidationException::withMessages([
                'payment' => 'Nominal pembayaran tidak valid.',
            ]);
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->midtrans_order_id ?: $this->makeOrderId($payment, $booking),
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => $this->customerDetails($booking),
            'custom_expiry' => [
                'expiry_duration' => self::PAYMENT_EXPIRY_MINUTES,
                'unit' => 'minute',
            ],
            'custom_field1' => $booking->booking_code,
            'custom_field2' => $payment->payment_type,
        ];

        if ($channel === 'qris') {
            return array_merge($payload, [
                'payment_type' => 'gopay',
            ]);
        }

        if ($channel === 'mandiri_bill') {
            return array_merge($payload, [
                'payment_type' => 'echannel',
                'echannel' => [
                    'bill_info1' => 'Payment For:',
                    'bill_info2' => 'JasaKu Booking',
                ],
            ]);
        }

        return array_merge($payload, [
            'payment_type' => 'bank_transfer',
            'bank_transfer' => [
                'bank' => str_replace('_va', '', $channel),
            ],
        ]);
    }

    private function customerDetails(Booking $booking): array
    {
        $customer = $booking->customer;
        $profile = $customer?->customerProfile;
        $name = trim((string) ($booking->customer_name ?: $customer?->name));
        $parts = preg_split('/\s+/', $name, 2) ?: [];

        return array_filter([
            'first_name' => $parts[0] ?? 'Customer',
            'last_name' => $parts[1] ?? null,
            'email' => $customer?->email,
            'phone' => $booking->customer_phone ?: $profile?->phone_number,
        ]);
    }

    private function makeOrderId(Payment $payment, Booking $booking): string
    {
        return 'JSK-' . $booking->booking_code . '-' . $payment->id;
    }

    private function parseMidtransTime(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function baseUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    private function serverKey(): string
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            throw ValidationException::withMessages([
                'payment' => 'MIDTRANS_SERVER_KEY belum diisi di file .env.',
            ]);
        }

        return $serverKey;
    }
}
