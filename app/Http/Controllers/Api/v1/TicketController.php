<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where('status', 'active')
                        ->where('event_date', '>=', now())
                        ->orderBy('event_date')
                        ->get();

        return response()->json([
            'status' => 'success',
            'tickets' => $tickets
        ]);
    }

    public function show($id)
    {
        $ticket = Ticket::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'ticket' => $ticket
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'event_date' => 'required|date|after:today',
            'quantity_available' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = Ticket::create($request->all() + ['status' => 'active', 'quantity_sold' => 0]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket created successfully',
            'ticket' => $ticket
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'ticket_name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'event_date' => 'sometimes|date',
            'quantity_available' => 'sometimes|integer|min:1',
            'description' => 'nullable|string',
            'location' => 'sometimes|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket updated successfully',
            'ticket' => $ticket
        ]);
    }

    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket deleted successfully'
        ]);
    }

    public function salesData($id)
    {
        $ticket = Ticket::findOrFail($id);
        $salesData = $ticket->transactions()
                           ->selectRaw('DATE(transaction_date) as date, SUM(quantity) as sales, SUM(total_price) as revenue')
                           ->groupBy('date')
                           ->orderBy('date')
                           ->get();

        return response()->json([
            'status' => 'success',
            'sales_data' => $salesData
        ]);
    }
}
