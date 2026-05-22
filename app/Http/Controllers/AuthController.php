<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller  
{
    public function register(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'phone'    => 'required|string|unique:users,phone',
        'password' => 'required|string|min:6',
    ]);

    $user = User::create([
        'name'     => $request->name,
        'phone'    => $request->phone,
        'password' => Hash::make($request->password),
        'role'     => 'user'
    ]);

    // إنشاء التوكن بعد التسجيل
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'تم إنشاء الحساب بنجاح',
        'user'    => $user,
        'token'   => $token,           // ← مهم
        'role'    => $user->role       // ← مهم
    ], 201);
}
 public function login(Request $request)
{
    $request->validate([
        'phone'    => 'required|string',
        'password' => 'required|string',
    ]);

    $user = User::where('phone', $request->phone)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'رقم الهاتف أو كلمة المرور غير صحيحة'
        ], 401);
    }

    // حذف أي توكنز قديمة لنفس المستخدم
    $user->tokens()->delete();

    // إنشاء توكن جديد
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'تم تسجيل الدخول بنجاح',
        'user'    => $user,
        'token'   => $token,      // ← مهم جداً
        'role'    => $user->role
    ]);
}
}