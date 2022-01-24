<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classroom;
use App\Models\ClassAttendee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ClassroomController extends Controller
{
    public function index()
    {
        return response([
            'attended_classes' => Auth::user()->attendedClasses
        ], 200);
    }

    public function store(Request $request)
    {
        $attrs = $request->validate([
            'name' => 'required|max:255',
            'invite_code' => 'required|string|max:255',
        ]);

        $classroom = Classroom::create([
            'name' => $attrs['name'],
            'invite_code' => $attrs['invite_code']
        ]);

        ClassAttendee::create([
            'user_id' => Auth::id(),
            'classroom_id' => $classroom->id,
            'role' => 'Instructor'
        ]);

        if (request()->hasFile('banner')) {
            $banner_image = $request->file('banner');
            $banner_image_file_path = 'banner-image-' . $classroom->id . '-' . time() . '.' . $banner_image->getClientOriginalExtension();
            $banner_image_file_path = $banner_image->storeAs('banner-images', $banner_image_file_path);
            $classroom->update([
                'banner_image_file_path' => $banner_image_file_path
            ]);
        }

        return response([
            'classroom' => Classroom::with('classAttendees')->find($classroom->id)
        ], 200);
    }

    public function show($classroomId)
    {
        return response([
            'classroom' => Classroom::find($classroomId)
        ], 200);
    }

    public function update(Request $request, $classroomId)
    {
        $classroom = Classroom::find($classroomId);

        $attrs = $request->validate([
            'name' => 'required|string|max:255',
            'invite_code' => 'required|string|max:255'
        ]);

        $classroom->update([
            'name' => $attrs['name'],
            'invite_code' => $attrs['invite_code']
        ]);

        if (request()->hasFile('banner')) {
            if (Storage::exists($classroom->banner_image_file_path)) {
                Storage::delete($classroom->banner_image_file_path);
            }
            $banner_image = $request->file('banner');
            $banner_image_file_path = 'banner-image-' . $classroom->id . '-' . time() . '.' . $banner_image->getClientOriginalExtension();
            $banner_image_file_path = $banner_image->storeAs('banner-images', $banner_image_file_path);
            $classroom->update([
                'banner_image_file_path' => $banner_image_file_path
            ]);
        }

        return response([
            'message' => 'Classroom updated.',
            'classroom' => Classroom::with('classAttendees')->find($classroom->id)
        ], 200);
    }

    public function destroy($classroomId)
    {
        $classAttendees = ClassAttendee::where('classroom_id', '=', $classroomId);
        foreach ($classAttendees as $classAttendee) {
            $classAttendee->delete();
        }

        $classroom =  Classroom::find($classroomId);
        if (Storage::exists($classroom->banner_image_file_path)) {
            Storage::delete($classroom->banner_image_file_path);
        }
        $classroom->delete();

        return response([
            'message' => 'Class deleted.',
        ], 200);
    }

    public function join($classroomId, $inviteCode)
    {
        $classroom = Classroom::find($classroomId);

        if ($classroom != null) {
            if ($classroom->invite_code == $inviteCode) {
                ClassAttendee::create([
                    'user_id' => Auth::id(),
                    'classroom_id' => $classroom->id,
                    'role' => 'Student'
                ]);

                return response([
                    'classroom' => Classroom::with('classAttendees')->find($classroom->id)
                ], 200);
            }
        }

        return response([
            'message' => 'Invalid credentials.',
        ], 401);
    }

    public function getAttendees($classroomId)
    {
        return response([
            'attendees' => Classroom::find($classroomId)->classAttendees
        ], 200);
    }
}
