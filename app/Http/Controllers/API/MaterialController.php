<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classroom;
use App\Models\Material;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    public function index($classroomId)
    {
        return response([
            'materials' => Classroom::find($classroomId)->materials
        ], 200);
    }

    public function store(Request $request, $classroomId)
    {
        $attrs = $request->validate([
            'title' => 'required|max:255',
        ]);

        $material = $request->file('material');
        $material_file_path = 'material-' . $attrs['title'] . '-' . time() . '.' . $material->getClientOriginalExtension();
        $material_file_path = $material->storeAs('materials', $material_file_path);

        $material = Material::create([
            'title' => $attrs['title'],
            'material_file_path' => $material_file_path,
            'classroom_id' => $classroomId
        ]);
        
        return response([
            'material' => $material
        ], 200);
    }

    public function show($materialId)
    {
        return response([
            'material' => Material::find($materialId)
        ], 200);
    }

    public function destroy($materialId)
    {
        $material = Material::find($materialId);
        if (Storage::exists($material->material_file_path)) {
            Storage::delete($material->material_file_path);
        }
        $material->delete();

        return response([
            'message' => 'Material deleted.',
        ], 200);
    }
}
