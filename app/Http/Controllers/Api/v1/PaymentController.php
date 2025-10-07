<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Ticket;
use App\Models\Ticket_code;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createTransaction(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'quantity' => 'required|integer|min:1',
            'session' => 'required|in:Pagi-Siang,Siang-Sore,Malam',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            // Cek ketersediaan tiket
            $ticket = Ticket::findOrFail($validated['ticket_id']);
            $availableTickets = $ticket->quantity_available - $ticket->quantity_sold;

            if ($availableTickets < $validated['quantity']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tiket tidak mencukupi'
                ], 400);
            }

            // Generate order ID
            $orderId = 'TIX-' . time() . '-' . Str::random(6);
            $totalAmount = $ticket->price * $validated['quantity'];

            // Buat transaksi
            $transaction = Transaction::create([
                'order_id' => $orderId,
                'user_id' => auth()->id(),
                'ticket_id' => $ticket->id,
                'quantity' => $validated['quantity'],
                'price_per_ticket' => $ticket->price,
                'total_amount' => $totalAmount,
                'session' => $validated['session'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'payment_status' => 'pending',
            ]);

            // Midtrans payload
            $payload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $totalAmount,
                ],
                'customer_details' => [
                    'first_name' => $validated['customer_name'],
                    'email' => $validated['customer_email'],
                    'phone' => $validated['customer_phone'],
                ],
                'item_details' => [
                    [
                        'id' => $ticket->id,
                        'price' => (int) $ticket->price,
                        'quantity' => $validated['quantity'],
                        'name' => $ticket->ticket_name,
                    ]
                ],
                'callbacks' => [
                    'finish' => env('FRONTEND_URL') . '/payment/success?order_id=' . $orderId,
                    'error' => env('FRONTEND_URL') . '/payment/failed?order_id=' . $orderId,
                    'pending' => env('FRONTEND_URL') . '/payment/pending?order_id=' . $orderId,
                ]
            ];

            $snapToken = Snap::getSnapToken($payload);

            // Update snap token
            $transaction->update(['snap_token' => $snapToken]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'snap_token' => $snapToken,
                    'order_id' => $orderId,
                    'transaction_id' => $transaction->id,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function handleNotification(Request $request)
    {
        try {
            $notif = new Notification();

            $transactionStatus = $notif->transaction_status;
            $paymentType = $notif->payment_type;
            $orderId = $notif->order_id;
            $fraudStatus = $notif->fraud_status ?? 'accept';

            $transaction = Transaction::where('order_id', $orderId)->firstOrFail();

            if ($transactionStatus == 'capture') {
                if ($paymentType == 'credit_card') {
                    if ($fraudStatus == 'challenge') {
                        $transaction->update(['payment_status' => 'pending']);
                    } else {
                        $this->processSuccessPayment($transaction, $notif);
                    }
                }
            } elseif ($transactionStatus == 'settlement') {
                $this->processSuccessPayment($transaction, $notif);
            } elseif ($transactionStatus == 'pending') {
                $transaction->update([
                    'payment_status' => 'pending',
                    'payment_type' => $paymentType,
                    'transaction_id' => $notif->transaction_id,
                ]);
            } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $transaction->update([
                    'payment_status' => $transactionStatus == 'deny' ? 'failed' : $transactionStatus,
                    'transaction_id' => $notif->transaction_id,
                ]);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function processSuccessPayment($transaction, $notif)
    {
        DB::beginTransaction();
        try {
            // Update status transaksi
            $transaction->update([
                'payment_status' => 'success',
                'payment_type' => $notif->payment_type,
                'transaction_id' => $notif->transaction_id,
                'paid_at' => now(),
            ]);

            // Update quantity sold tiket
            $ticket = $transaction->ticket;
            $ticket->increment('quantity_sold', $transaction->quantity);

            // Generate ticket codes
            for ($i = 0; $i < $transaction->quantity; $i++) {
                $ticketCode = $this->generateTicketCode($transaction);
                Ticket_code::create([
                    'transaction_id' => $transaction->id,
                    'ticket_code' => $ticketCode,
                ]);
            }

            DB::commit();

            // TODO: Kirim email notifikasi dengan tiket

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function generateTicketCode($transaction)
    {
        return 'TICKET-' . $transaction->id . '-' . Str::upper(Str::random(8));
    }

    public function checkStatus($orderId)
    {
        $transaction = Transaction::where('order_id', $orderId)
            ->with(['ticket', 'ticketCodes'])
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $transaction
        ]);
    }
}
