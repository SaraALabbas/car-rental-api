<?php

namespace App\Http\Controllers;


use App\Models\Booking;
use App\Models\Car;
use App\Models\User;


use App\Models\Contract;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // 📌 إضافة حجز
    public function store(Request $request)
{
    // التحقق من تسجيل الدخول
    if (!$request->user()) {
        return response()->json([
            'message' => 'يجب تسجيل الدخول أولاً'
        ], 401);
    }

    $request->validate([
        'car_id' => 'required|exists:cars,id',
        'full_name' => 'required|string|max:255',
        'phone' => 'required|string',
        'pickup_date' => 'required|date',
        'return_date' => 'required|date|after_or_equal:pickup_date',
        'pickup_time' => 'required',
        'return_time' => 'required',
        'id_front' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        'id_back' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        'payment_image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        'delivery' => 'boolean',
        'delivery_location' => 'nullable|string|max:255',
    ]);

    // رفع الصور
    $idFront = $this->uploadToSupabase($request->file('id_front'));
$idBack = $this->uploadToSupabase($request->file('id_back'));
$payment = $this->uploadToSupabase($request->file('payment_image'));

    $exists = Booking::where('car_id', $request->car_id)
        ->where('status', 'accepted')
        ->where(function ($q) use ($request) {
            $q->whereBetween('pickup_date', [
                $request->pickup_date,
                $request->return_date
            ])
            ->orWhereBetween('return_date', [
                $request->pickup_date,
                $request->return_date
            ])
            ->orWhere(function ($q2) use ($request) {
                $q2->where('pickup_date', '<=', $request->pickup_date)
                   ->where('return_date', '>=', $request->return_date);
            });
        })
        ->exists();

    if ($exists) {
        return response()->json([
            'message' => 'السيارة محجوزة في هذه الفترة'
        ], 400);
    }

    $booking = Booking::create([
        'user_id' => $request->user()->id,
        'car_id' => $request->car_id,
        'full_name' => $request->full_name,
        'phone' => $request->phone,
        'pickup_date' => $request->pickup_date,
        'return_date' => $request->return_date,
        'pickup_time' => $request->pickup_time,
        'return_time' => $request->return_time,
        'delivery' => $request->boolean('delivery', false),
        'delivery_location' => $request->delivery_location,
        'id_front' => $idFront,
        'id_back' => $idBack,
        'payment_image' => $payment,
        'status' => 'pending',
        'rejection_reason' => null,
    ]);

    return response()->json([
        'message' => 'تم الحجز بنجاح',
        'booking' => $booking
    ], 201);
}
private function uploadToSupabase($file)
{
    if (!$file || !$file->isValid()) {
        return null;
    }

    $filename = time().'_'.$file->getClientOriginalName();

    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'Bearer '.env('SUPABASE_KEY'),
        'apikey' => env('SUPABASE_KEY'),
    ])->attach(
        'file',
        fopen($file->getRealPath(), 'r'),
        $filename
    )->post(env('SUPABASE_URL').'/storage/v1/object/bookings/'.$filename);

    return env('SUPABASE_URL').'/storage/v1/object/public/bookings/'.$filename;
}


    // 📌 عرض حجوزات المستخدم
    public function myBookings(Request $request)
{
    $bookings = Booking::where('user_id', $request->user()->id)
        ->with('car')
        ->orderBy('created_at', 'desc')
        ->get();


    return $bookings;
}

    // 📌 عرض كل الحجوزات (ADMIN)
    public function index()
{
    return Booking::with('car')->get();
}

public function approve($id)
{
    $booking = Booking::findOrFail($id);
    $car = Car::findOrFail($booking->car_id);

    // ❌ إذا السيارة محجوزة
    if ($car->available == 0) {
        return response()->json([
            'message' => 'السيارة غير متاحة حالياً'
        ], 400);
    }

    // ✅ قبول الطلب
    $booking->status = 'accepted';
    $booking->rejection_reason = null;
    $booking->save();
    


    // 🔒 إغلاق السيارة
    $car->available = 0;
    $car->save();

        // 🔥 إنشاء العقد
    $last = Contract::orderBy('contract_number', 'desc')->first();
$number = $last ? $last->contract_number + 1 : 1;

Contract::create([
    'booking_id' => $booking->id,
    'user_id' => $booking->user_id,
    'contract_number' => $number,
    'signed_at' => now(),
    'status' => 'active',
]);



    // ❌ رفض باقي الطلبات
    $otherBookings = Booking::where('car_id', $booking->car_id)
        ->where('status', 'pending')
        ->where('id', '!=', $booking->id)
        ->get();

    foreach ($otherBookings as $b) {
        $b->status = 'rejected';
        $b->rejection_reason = 'تم حجز السيارة من قبل شخص آخر';
        $b->save();
    }

    

    return response()->json([
        'message' => 'تم قبول الطلب وإنشاء العقد'
    ]);
}
public function reject(Request $request, $id)
{
    $booking = Booking::findOrFail($id);

    $booking->status = 'rejected';
    $booking->rejection_reason = $request->reason;
    $booking->save();
    

    return response()->json(['message' => 'تم رفض الطلب']);
}
public function show($id)
{
    $booking = Booking::with(['car', 'user'])->findOrFail($id);

    return response()->json($booking);
}
}
