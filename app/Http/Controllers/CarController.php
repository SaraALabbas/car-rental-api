<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Carbon\Carbon;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;


use Illuminate\Http\Request;

class CarController extends Controller
{
    // 📌 عرض كل السيارات
    
public function index()
{
    // 🔄 رجّع السيارات اللي انتهى حجزها
    $expiredBookings = Booking::where('status', 'accepted')
        ->where('return_date', '<', Carbon::today())
        ->get();

    foreach ($expiredBookings as $booking) {
        $car = $booking->car;
        if ($car) {
            $car->available = 1;
            $car->save();
        }

        // نغير الحالة (اختياري)
        $booking->status = 'finished';
        $booking->save();
    }

    // ✅ عرض فقط السيارات المتاحة
    $cars = Car::where('available', 1)->get()->map(function ($car) {
    return $car;
});
    return response()->json($cars);
}
    
    public function show($id)
{
    $car = Car::find($id);

    if (!$car) {
        return response()->json([
            'message' => 'Car not found'
        ], 404);
    }

    return response()->json([
        'id' => $car->id,
        'name' => $car->name,
        'plate_number' => $car->plate_number,
        'color' => $car->color,
        'daily_km' => $car->daily_km,
        'price' => $car->price,
        'model_year' => $car->model_year,
        'image1' => $car->image1,
'image2' => $car->image2,
'image3' => $car->image3,
    ]);
}
    // 📌 إضافة سيارة (ADMIN)
    public function store(Request $request)
    {
        
        // ✅ تحقق الأدمن
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
    'name' => 'required|string',
    'plate_number' => 'required|string|unique:cars,plate_number',
    'color' => 'required|string',
    'daily_km' => 'required|integer|min:0',
    'price' => 'required|numeric|min:0',
    'model_year' => 'nullable|integer',
    'image1' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    'image2' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    'image3' => 'required|image|mimes:jpg,jpeg,png|max:2048',
]);

        // رفع الصور
       $image1 = $this->uploadToSupabase($request->file('image1'));
$image2 = $this->uploadToSupabase($request->file('image2'));
$image3 = $this->uploadToSupabase($request->file('image3'));
        $car = Car::create([
            'name' => $request->name,
            'plate_number' => $request->plate_number,
            'color' => $request->color,
            'daily_km' => $request->daily_km,
            'price' => $request->price,
            'model_year' => $request->model_year,
            'image1' => $image1,
            'image2' => $image2,
            'image3' => $image3,
        ]);

        return response()->json([
            'message' => 'تمت إضافة السيارة',
            'car' => $car
        ], 201);
    }

private function uploadToSupabase($file)
{
    if (!$file) {
        return null;
    }

    $filename = time().'_'.$file->getClientOriginalName();

    Http::withHeaders([
        'Authorization' => 'Bearer '.env('SUPABASE_KEY'),
        'apikey' => env('SUPABASE_KEY'),
    ])->attach(
        'file',
        file_get_contents($file),
        $filename
    )->post(env('SUPABASE_URL').'/storage/v1/object/cars/'.$filename);

    return env('SUPABASE_URL').'/storage/v1/object/public/cars/'.$filename;
}
    // 📌 تعديل سيارة
    public function update(Request $request, $id)
{
    $car = Car::findOrFail($id);

    $data = $request->only([
        'name',
        'plate_number',
        'color',
        'daily_km',
        'price',
        'model_year', 
    ]);

    if ($request->hasFile('image1')) {
    $data['image1'] = $this->uploadToSupabase($request->file('image1'));
}

if ($request->hasFile('image2')) {
    $data['image2'] = $this->uploadToSupabase($request->file('image2'));
}

if ($request->hasFile('image3')) {
    $data['image3'] = $this->uploadToSupabase($request->file('image3'));
}
    $car->update($data);

    return response()->json([
        'message' => 'تم تعديل السيارة',
        'car' => $car
    ]);
}

    // 📌 حذف سيارة
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $car = Car::findOrFail($id);
        $car->delete();

        return response()->json([
            'message' => 'تم حذف السيارة'
        ]);
    }
}