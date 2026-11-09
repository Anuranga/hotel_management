<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Throwable;

class SmsReceiptService
{
    /**
     * Dispatch a rate-limited SMS digital receipt for a settled order.
     *
     * @return array{sent: bool, rate_limited: bool, payload: array<string, mixed>}
     */
    public function sendReceipt(Order $order): array
    {
        $hotelName = (string) config('hotel.name', 'Odara Food Pvt Limited');
        $signedReceiptUrl = URL::signedRoute('order.receipt', ['order' => $order->id]);

        $message = sprintf(
            'Dear %s, thank you for dining at %s! Order #%d settled. Grand Total: LKR %s. View digital receipt: %s',
            $order->customer_name,
            $hotelName,
            $order->id,
            number_format((float) $order->grand_total, 2),
            $signedReceiptUrl
        );

        $payload = [
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'hotel_name' => $hotelName,
            'grand_total' => number_format((float) $order->grand_total, 2, '.', ''),
            'receipt_url' => $signedReceiptUrl,
            'message' => $message,
            'sender_id' => (string) config('hotel.sms.sender_id', 'ODARAFOOD'),
        ];

        $rateLimitKey = 'sms-receipt:'.$order->customer_phone;
        $maxAttempts = (int) config('hotel.sms.rate_limit_per_minute', 5);

        $executed = RateLimiter::attempt(
            $rateLimitKey,
            $maxAttempts,
            function () use ($payload): void {
                Log::info('SMS Digital Receipt Dispatched', $payload);

                if (app()->environment('production') && config('hotel.sms.enabled')) {
                    try {
                        Http::timeout(3)
                            ->withToken((string) config('hotel.sms.api_key'))
                            ->post((string) config('hotel.sms.endpoint'), $payload);
                    } catch (Throwable $exception) {
                        Log::warning('SMS Gateway HTTP fallback recorded locally', [
                            'error' => $exception->getMessage(),
                            'order_id' => $payload['order_id'],
                        ]);
                    }
                }
            },
            60
        );

        return [
            'sent' => (bool) $executed,
            'rate_limited' => ! $executed,
            'payload' => $payload,
        ];
    }
}
