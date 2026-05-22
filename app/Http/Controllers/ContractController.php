<?php

namespace App\Http\Controllers;
use App\Models\Contract;
use App\Models\Booking;
use App\Models\Car;

use Illuminate\Http\Request;

class ContractController extends Controller
{
    //
        // جميع العقود (admin)
    public function index()
{
    try {
        return response()->json(
            Contract::with(['booking.car', 'user'])->latest()->get()
        );
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

    // عقد واحد
    public function show($id)
    {
        return Contract::with(['booking.car', 'user'])->findOrFail($id);
    }

    // عقود المستخدم
    public function myContracts(Request $request)
    {
        $user = $request->user();

        return Contract::where('user_id', $user->id)
            ->with(['booking.car'])
            ->latest()
            ->get();
    }
}
