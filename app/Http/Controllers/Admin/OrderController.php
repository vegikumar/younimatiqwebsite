<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use App\Models\CampBooking;
use App\Services\RefundService;
use App\Services\CurrencyService;
use App\Services\TimezoneService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    /**
     * Currency symbol map for display.
     */
    private static array $symbols = [
        'USD' => '$',
        'INR' => '₹',
        'AED' => 'AED ',
        'EUR' => '€',
        'GBP' => '£',
        'AUD' => 'A$',
        'SGD' => 'S$',
        'OMR' => 'OMR ',
    ];

    public function index(Request $request)
    {
        $payments = Payment::with(['student', 'course'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $orders = Order::with(['user', 'course', 'payment'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $campBookings = CampBooking::orderBy('created_at', 'desc')->take(20)->get();

        // Group revenue by currency so we don't mix incompatible currencies
        $revenueGrouped = Payment::where('status', 'completed')
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        // Format revenue per currency for display
        $revenueFormatted = [];
        foreach ($revenueGrouped as $currency => $total) {
            $symbol = self::$symbols[strtoupper($currency)] ?? (strtoupper($currency) . ' ');
            $revenueFormatted[] = $symbol . number_format($total, 2);
        }

        // Keep a fallback for legacy use
        $totalRevenue = array_sum($revenueGrouped); // raw number (multi-currency, just for count)
        $totalRevenueDisplay = !empty($revenueFormatted) ? implode(' + ', $revenueFormatted) : '$0.00';

        $formatAmount = function ($amount, ?string $currency) {
            $code = strtoupper($currency ?? 'USD');
            $symbol = self::$symbols[$code] ?? ($code.' ');

            return $symbol.number_format((float) ($amount ?? 0), 2);
        };

        $viewerTz = $this->viewerTimezone($request);
        $formatTs = fn (?CarbonInterface $moment) => $this->formatInViewerTimezone($moment, $viewerTz);

        return \App\Support\InertiaPage::react('Admin/Orders/Index', [
            'revenueGrouped' => collect($revenueGrouped)->map(fn ($total, $currency) => [
                'currency' => strtoupper($currency),
                'formatted' => $formatAmount($total, $currency),
            ])->values()->all(),
            'ordersCount' => $orders->count(),
            'campBookingsCount' => $campBookings->count(),
            'exportUrl' => route('admin.orders.export'),
            'viewerTimezone' => $viewerTz,
            'payments' => \App\Support\AdminInertiaProps::paginate($payments, function ($payment) use ($formatAmount, $formatTs) {
                $currency = strtoupper($payment->currency ?? 'USD');
                // Completed rows use updated_at (when gateway confirmed payment).
                $paidAt = $payment->status === 'completed'
                    ? ($payment->updated_at ?? $payment->created_at)
                    : $payment->created_at;

                return [
                    'id' => $payment->id,
                    'studentName' => $payment->student?->name ?? 'N/A',
                    'courseTitle' => $payment->course?->title ?? 'N/A',
                    'amountFormatted' => $formatAmount($payment->amount, $currency),
                    'currency' => $currency,
                    'gateway' => ucfirst($payment->gateway ?? 'N/A'),
                    'status' => $payment->status,
                    'createdAtLabel' => $formatTs($paidAt),
                    'refundUrl' => route('admin.orders.refund', $payment),
                ];
            }),
            'orders' => $orders->map(function ($order) use ($formatAmount, $formatTs) {
                $currency = strtoupper($order->payment->currency ?? 'USD');

                return [
                    'orderNumber' => $order->order_number ?? $order->id,
                    'userName' => $order->user?->name ?? 'N/A',
                    'courseTitle' => $order->course?->title ?? 'N/A',
                    'amountFormatted' => $formatAmount($order->amount, $currency),
                    'currency' => $currency,
                    'paymentMethod' => ucfirst($order->payment_method ?? 'N/A'),
                    'status' => $order->status,
                    'createdAtLabel' => $formatTs($order->created_at),
                ];
            })->values()->all(),
            'campBookings' => $campBookings->map(function ($booking) use ($formatAmount, $formatTs) {
                $currency = strtoupper($booking->currency ?? 'USD');

                return [
                    'campName' => $booking->camp_name,
                    'childName' => $booking->child_name,
                    'parentName' => $booking->parent_name,
                    'email' => $booking->email,
                    'amountFormatted' => (float) $booking->amount > 0 ? $formatAmount($booking->amount, $currency) : null,
                    'currency' => (float) $booking->amount > 0 ? $currency : null,
                    'paymentMethod' => ucfirst($booking->payment_method ?? 'Free Trial'),
                    'paymentStatus' => $booking->payment_status,
                    'createdAtLabel' => $formatTs($booking->created_at),
                ];
            })->values()->all(),
        ]);
    }

    /**
     * Admin panel location timezone: geo session → admin profile → app default.
     */
    private function viewerTimezone(Request $request): string
    {
        $candidate = session('timezone')
            ?: $request->user('admin')?->timezone
            ?: $request->user()?->timezone
            ?: config('app.timezone', 'UTC');

        return TimezoneService::sanitizeTimezone(is_string($candidate) ? $candidate : 'UTC');
    }

    private function formatInViewerTimezone(?CarbonInterface $moment, string $timezone): ?string
    {
        if (! $moment) {
            return null;
        }

        try {
            return $moment->copy()->timezone($timezone)->format('M d, Y g:i A T');
        } catch (\Throwable) {
            return $moment->copy()->timezone('UTC')->format('M d, Y g:i A T');
        }
    }

    public function export(Request $request): StreamedResponse
    {
        $payments = Payment::with(['student', 'course'])->orderBy('created_at', 'desc')->get();
        $viewerTz = $this->viewerTimezone($request);

        return response()->streamDownload(function () use ($payments, $viewerTz) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Paid at', 'Timezone', 'Student', 'Course', 'Amount', 'Currency', 'Gateway', 'Status', 'Transaction ID']);
            foreach ($payments as $p) {
                $paidAt = $p->status === 'completed'
                    ? ($p->updated_at ?? $p->created_at)
                    : $p->created_at;
                fputcsv($out, [
                    $p->id,
                    $this->formatInViewerTimezone($paidAt, $viewerTz),
                    $viewerTz,
                    $p->student->email ?? '',
                    $p->course->title ?? '',
                    $p->amount,
                    strtoupper($p->currency ?? 'USD'),
                    $p->gateway,
                    $p->status,
                    $p->transaction_id,
                ]);
            }
            fclose($out);
        }, 'younimatiq-orders-' . date('Y-m-d') . '.csv');
    }

    public function refund(Payment $payment, RefundService $refundService)
    {
        $result = $refundService->refundPayment($payment);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
