<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classroom;
use App\Models\Assignment;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    public function index($classroomId)
    {
        return response([
            'assignments' => Classroom::find($classroomId)->assignments
        ], 200);
    }

    public function store(Request $request, $classroomId)
    {
        $attrs = $request->validate([
            'title' => 'required|max:255',
            'deadline' => 'required'
        ]);

        $bucket = app('firebase.storage')->getBucket();
        $storage_path = 'assignments/';
        $assignment = $request->file('assignment');
        $assignment_file_name = 'assignment-' . $attrs['title'] . '-' . time() . '.' . $assignment->getClientOriginalExtension();
        $assignment_file_path =  $storage_path . $assignment_file_name;

        $localfolder = public_path('firebase-temp-uploads') . '/';

        if ($assignment->move($localfolder, $assignment_file_name)) {
            $uploadedfile = fopen($localfolder . $assignment_file_name, 'r');

            $bucket->upload($uploadedfile, ['name' => $assignment_file_path]);

            unlink($localfolder . $assignment_file_name);
        } else {
            abort(500);
        }

        $assignment = Assignment::create([
            'title' => $attrs['title'],
            'deadline' => Carbon::createFromFormat('Y-m-d H:i', $attrs['deadline'])->format('Y-m-d H:i'),
            'assignment_file_path' => $assignment_file_path,
            'classroom_id' => $classroomId
        ]);

        return response([
            'assignment' => $assignment
        ], 200);
    }

    public function show($assignmentId)
    {
        return response([
            'assignment' => Assignment::find($assignmentId)
        ], 200);
    }

    public function destroy($assignmentId)
    {
        $assignment = Assignment::find($assignmentId);

        $bucket = app('firebase.storage')->getBucket();

        if ($assignment->assignment_file_path != NULL) {
            $file = $bucket->object($assignment->assignment_file_path);
            if ($file->exists()) {
                $file->delete();
            }
        }

        $assignment->delete();

        return response([
            'message' => 'Assignment deleted.',
        ], 200);
    }
}
