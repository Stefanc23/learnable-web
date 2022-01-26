<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classroom;
use App\Models\Material;

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

        $bucket = app('firebase.storage')->getBucket();
        $storage_path = 'materials/';
        $material = $request->file('material');
        $material_file_name = 'material-' . $attrs['title'] . '-' . time() . '.' . $material->getClientOriginalExtension();
        $material_file_path =  $storage_path . $material_file_name;

        $localfolder = public_path('firebase-temp-uploads') . '/';

        if ($material->move($localfolder, $material_file_name)) {
            $uploadedfile = fopen($localfolder . $material_file_name, 'r');

            $bucket->upload($uploadedfile, ['name' => $material_file_path]);

            unlink($localfolder . $material_file_name);
        } else {
            abort(500);
        }

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
        
        $bucket = app('firebase.storage')->getBucket();

        if ($material->material_file_path != NULL) {
            $file = $bucket->object($material->material_file_path);
            if ($file->exists()) {
                $file->delete();
            }
        }
        
        $material->delete();

        return response([
            'message' => 'Material deleted.',
        ], 200);
    }
}
