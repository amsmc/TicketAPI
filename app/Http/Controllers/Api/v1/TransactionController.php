<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Ticket;
use App\Models\Finance;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function purchase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|exists:tickets,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = Ticket::findOrFail($request->ticket_id);

        if ($ticket->available_tickets < $request->quantity) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not enough tickets available'
            ], 400);
        }

        $totalPrice = $ticket->price * $request->quantity;

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'ticket_id' => $request->ticket_id,
            'quantity' => $request->quantity,
            'total_price' => $totalPrice,
            'transaction_date' => now(),
            'payment_status' => 'pending'
        ]);

        // Generate QR Code
        $qrData = json_encode([
            'transaction_id' => $transaction->id,
            'reference' => $transaction->reference_number,
            'user' => $transaction->user->name,
            'event' => $ticket->ticket_name,
            'quantity' => $transaction->quantity
        ]);

        $qrCode = base64_encode(QrCode::format('png')->size(200)->generate($qrData));
        $transaction->update(['qr_code' => $qrCode]);

        // Update ticket quantity
        $ticket->increment('quantity_sold', $request->quantity);

        // Create finance record
        Finance::create([
            'transaction_id' => $transaction->id,
            'gross_amount' => $totalPrice,
            'description' => "Ticket purchase: {$ticket->ticket_name}"
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase successful',
            'transaction' => $transaction->load(['ticket', 'user'])
        ], 201);
    }

    public function userHistory(Request $request)
    {
        $transactions = $request->user()->transactions()
            ->with('ticket')
            ->orderBy('transaction_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'transactions' => $transactions
        ]);
    }

    public function allTransactions(Request $request)
    {
        $transactions = Transaction::with(['user', 'ticket'])
            ->orderBy('transaction_date', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'transactions' => $transactions
        ]);
    }

    public function verifyPayment(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:confirmed,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction->update(['payment_status' => $request->payment_status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment status updated successfully',
            'transaction' => $transaction
        ]);
    }

    public function downloadTicket($id)
    {
        $transaction = Transaction::with(['ticket', 'user'])->findOrFail($id);

        if ($transaction->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'success',
            'e_ticket' => [
                'reference_number' => $transaction->reference_number,
                'event_name' => $transaction->ticket->ticket_name,
                'event_date' => $transaction->ticket->event_date,
                'quantity' => $transaction->quantity,
                'total_price' => $transaction->total_price,
                'qr_code' => $transaction->qr_code,
                'customer_name' => $transaction->user->name
            ]
        ]);
    }
    public function show(Request $request, $orderId)
    {
        try {
            $user = $request->user();

            $transaction = Transaction::where('order_id', $orderId)
                ->where('user_id', $user->id)
                ->with('ticket')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            \Log::error('Get transaction error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch transaction'
            ], 500);
        }
    }
}
