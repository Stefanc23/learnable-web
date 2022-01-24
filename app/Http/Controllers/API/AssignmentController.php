<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classroom;
use App\Models\Assignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

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

        $assignment = $request->file('assignment');
        $assignment_file_path = 'assignment-' . $attrs['title'] . '-' . time() . '.' . $assignment->getClientOriginalExtension();
        $assignment_file_path = $assignment->storeAs('assignments', $assignment_file_path);

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
        if (Storage::exists($assignment->assignment_file_path)) {
            Storage::delete($assignment->assignment_file_path);
        }
        $assignment->delete();

        return response([
            'message' => 'Assignment deleted.',
        ], 200);
    }
}
