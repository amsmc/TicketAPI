<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    public function index()
    {
        try {
            // Hapus where('status', 'active') karena kolom status tidak ada
            $tickets = Ticket::where('event_date', '>=', now())
                            ->where('quantity_available', '>', 0) // Hanya tiket yang masih tersedia
                            ->orderBy('event_date')
                            ->get();

            return response()->json([
                'status' => 'success',
                'data' => $tickets // Ubah 'tickets' jadi 'data' untuk konsistensi
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching tickets: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $ticket = Ticket::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $ticket
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }
    }

     public function checkAvailability($id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }

        $available = $ticket->quantity_available - $ticket->quantity_sold;

        return response()->json([
            'success' => true,
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_name' => $ticket->ticket_name,
                'available' => $available,
                'can_purchase' => $available > 0 && $ticket->status === 'active'
            ]
        ]);
    }

    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'ticket_name' => 'required|string|max:255',
    //         'price' => 'required|numeric|min:0',
    //         'event_date' => 'required|date|after:today',
    //         'quantity_available' => 'required|integer|min:1',
    //         'image_url' => 'nullable|url'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     try {
    //         $ticket = Ticket::create($request->all());

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Ticket created successfully',
    //             'data' => $ticket
    //         ], 201);

    //     } catch (\Exception $e) {
    //         Log::error('Error creating ticket: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to create ticket'
    //         ], 500);
    //     }
    // }

    // public function update(Request $request, $id)
    // {
    //     try {
    //         $ticket = Ticket::findOrFail($id);

    //         $validator = Validator::make($request->all(), [
    //             'ticket_name' => 'sometimes|string|max:255',
    //             'price' => 'sometimes|numeric|min:0',
    //             'event_date' => 'sometimes|date',
    //             'quantity_available' => 'sometimes|integer|min:1',
    //             'image_url' => 'nullable|url'
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $ticket->update($request->all());

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Ticket updated successfully',
    //             'data' => $ticket
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to update ticket'
    //         ], 500);
    //     }
    // }

    // public function destroy($id)
    // {
    //     try {
    //         $ticket = Ticket::findOrFail($id);
    //         $ticket->delete();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Ticket deleted successfully'
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to delete ticket'
    //         ], 500);
    //     }
    // }

    // public function salesData($id)
    // {
    //     try {
    //         $ticket = Ticket::findOrFail($id);

    //         // Implementasi sales data jika ada relasi transactions
    //         // $salesData = $ticket->transactions()...

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => [] // Sementara kosong sampai ada relasi transactions
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to get sales data'
    //         ], 500);
    //     }
    // }
}
