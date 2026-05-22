<?php

namespace App\Http\Controllers;

use App\Models\Instruction;
use Illuminate\Http\Request;

class InstructionController extends Controller
{
    // جلب جميع التعليمات
    public function index()
    {
        return Instruction::orderBy('id', 'asc')->get();
    }

    // إضافة تعليمات جديدة
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
        ]);

        $instruction = Instruction::create([
            'title' => $request->title,
            'content' => $request->content,
            'icon' => $request->icon,
        ]);

        return response()->json($instruction);
    }

    // تعديل
    public function update(Request $request, $id)
    {
        $instruction = Instruction::findOrFail($id);

        $instruction->update([
            'title' => $request->title,
            'content' => $request->content,
            'icon' => $request->icon,
        ]);

        return response()->json($instruction);
    }

    // حذف
    public function destroy($id)
    {
        Instruction::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}