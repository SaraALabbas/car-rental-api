<?php

namespace App\Http\Controllers;


use App\Models\Booking;
use App\Models\Car;
use App\Models\User;

use Carbon\Carbon;
use App\Models\Contract;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // 📌 إضافة حجز
    public function store(Request $request)
{
    
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
// جلب السيارة
$car = Car::findOrFail($request->car_id);

// السعر اليومي
$dailyPrice = $car->price;

// حساب عدد الأيام
$days = Carbon::parse($request->pickup_date)
    ->diffInDays(Carbon::parse($request->return_date));

$days = max($days, 1);

// حساب نسبة الخصم
$discount = 0;

if ($days >= 3) {
    $discount = 12 + ($days - 3);
}

// حساب الأسعار
$totalPrice = $dailyPrice * $days;

$finalPrice = $totalPrice - (($totalPrice * $discount) / 100);





    do {
    $bookingNumber = 'BK-' . strtoupper(substr(uniqid(), -8));
} while (Booking::where('booking_number', $bookingNumber)->exists());

    $booking = Booking::create([
        'booking_number' => $bookingNumber,
        'user_id' => optional($request->user())->id,
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
        'daily_price' => $dailyPrice,
'discount_percentage' => $discount,
'final_price' => $finalPrice,
    ]);

    return response()->json([
    'message' => 'تم الحجز بنجاح',
    'booking_number' => $booking->booking_number,
    'daily_price' => $booking->daily_price,
    'discount_percentage' => $booking->discount_percentage,
    'final_price' => $booking->final_price,
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

public function trackBooking(Request $request)
{
    $request->validate([
        'booking_number' => 'required|string',
        'phone' => 'required|string',
    ]);

    $booking = Booking::with('car')
        ->where('booking_number', $request->booking_number)
        ->where('phone', $request->phone)
        ->first();

    if (!$booking) {
        return response()->json([
            'message' => 'لم يتم العثور على الحجز'
        ], 404);
    }

    return response()->json([
        'full_name' => $booking->full_name,
'phone' => $booking->phone,
        'booking_number' => $booking->booking_number,
        'status' => $booking->status,
        'rejection_reason' => $booking->rejection_reason,

        'car' => [
            'name' => $booking->car->name,
            'model' => $booking->car->model_year,
            'color' => $booking->car->color,
            'image' => $booking->car->image,
        ],

        'final_price' => $booking->final_price,

        'pickup_date' => $booking->pickup_date,
        'pickup_time' => $booking->pickup_time,

        'return_date' => $booking->return_date,
        'return_time' => $booking->return_time,

        'delivery' => $booking->delivery,
        'delivery_location' => $booking->delivery_location,

        'id_front' => $booking->id_front,
        'id_back' => $booking->id_back,
        'payment_image' => $booking->payment_image,
    ]);
}

// 📌 عرض كل الحجوزات (ADMIN)
public function index(Request $request)
{
    $lastId = $request->query('last_id');

    // Polling
    if ($lastId) {
        $bookings = Booking::with('car')
            ->where('id', '>', $lastId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($bookings);
    }

    // أول تحميل للصفحة
    $bookings = Booking::with('car')
        ->orderBy('created_at', 'desc')
        ->paginate(2);

    return response()->json($bookings);
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
        $b->rejection_reason = 'تم حجز السيارة من قبل شخص آخر, يرجى اختيار موعد أو سيارة اخرى . شكرا لتفهمكم.يرجى التواصل مع المكتب لاسترجاع المبلغ المدفوع';
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
