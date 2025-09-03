<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Ticket;
use App\Models\Finance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function adminDashboard()
    {
        $totalIncome = Finance::sum('gross_amount');
        $totalTransactions = Transaction::count();
        $pendingPayments = Transaction::where('payment_status', 'pending')->count();
        $confirmedPayments = Transaction::where('payment_status', 'confirmed')->count();

        $monthlyIncome = Finance::whereMonth('created_at', Carbon::now()->month)
                              ->whereYear('created_at', Carbon::now()->year)
                              ->sum('gross_amount');

        $recentTransactions = Transaction::with(['user', 'ticket'])
                                       ->orderBy('created_at', 'desc')
                                       ->limit(10)
                                       ->get();

        return response()->json([
            'status' => 'success',
            'dashboard_data' => [
                'total_income' => $totalIncome,
                'total_transactions' => $totalTransactions,
                'pending_payments' => $pendingPayments,
                'confirmed_payments' => $confirmedPayments,
                'monthly_income' => $monthlyIncome,
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }

    public function ownerDashboard()
    {
        $totalEvents = Ticket::count();
        $activeEvents = Ticket::where('status', 'active')->count();
        $totalRevenue = Finance::sum('gross_amount');
        $totalTicketsSold = Transaction::sum('quantity');

        // Sales data for chart
        $salesData = Transaction::selectRaw('DATE(transaction_date) as date, SUM(quantity) as tickets_sold, SUM(total_price) as revenue')
                               ->groupBy('date')
                               ->orderBy('date')
                               ->limit(30)
                               ->get();

        $topEvents = Ticket::withSum('transactions', 'quantity')
                          ->orderBy('transactions_sum_quantity', 'desc')
                          ->limit(5)
                          ->get();

        return response()->json([
            'status' => 'success',
            'dashboard_data' => [
                'total_events' => $totalEvents,
                'active_events' => $activeEvents,
                'total_revenue' => $totalRevenue,
                'total_tickets_sold' => $totalTicketsSold,
                'sales_data' => $salesData,
                'top_events' => $topEvents
            ]
        ]);
    }

    public function exportReport(Request $request)
    {
        $transactions = Transaction::with(['user', 'ticket', 'finance'])
                                 ->when($request->start_date, function($query, $startDate) {
                                     return $query->whereDate('transaction_date', '>=', $startDate);
                                 })
                                 ->when($request->end_date, function($query, $endDate) {
                                     return $query->whereDate('transaction_date', '<=', $endDate);
                                 })
                                 ->get();

        $csvData = $transactions->map(function($transaction) {
            return [
                'Reference' => $transaction->reference_number,
                'Customer' => $transaction->user->name,
                'Email' => $transaction->user->email,
                'Event' => $transaction->ticket->ticket_name,
                'Quantity' => $transaction->quantity,
                'Total Price' => $transaction->total_price,
                'Payment Status' => $transaction->payment_status,
                'Transaction Date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
                'Gross Amount' => $transaction->finance ? $transaction->finance->gross_amount : 0
            ];
        });

        return response()->json([
            'status' => 'success',
            'csv_data' => $csvData,
            'filename' => 'transactions_report_' . now()->format('Y_m_d') . '.csv'
        ]);
    }

}
