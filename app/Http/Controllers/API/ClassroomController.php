<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classroom;
use App\Models\ClassAttendee;
use Illuminate\Support\Facades\Auth;

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
            $bucket = app('firebase.storage')->getBucket();
            $storage_path = 'banner-images/';
            $banner_image = $request->file('banner');
            $banner_image_file_name = 'banner-image-' . $classroom->id . '-' . time() . '.' . $banner_image->getClientOriginalExtension();
            $banner_image_file_path =  $storage_path . $banner_image_file_name;

            $localfolder = public_path('firebase-temp-uploads') . '/';

            if ($banner_image->move($localfolder, $banner_image_file_name)) {
                $uploadedfile = fopen($localfolder . $banner_image_file_name, 'r');

                $bucket->upload($uploadedfile, ['name' => $banner_image_file_path]);

                unlink($localfolder . $banner_image_file_name);

                $classroom->update([
                    'banner_image_file_path' => $banner_image_file_path
                ]);
            } else {
                abort(500);
            }

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
            $bucket = app('firebase.storage')->getBucket();

            if ($classroom->banner_image_file_path != NULL) {
                $banner_image = $bucket->object($classroom->banner_image_file_path);
                if ($banner_image->exists()) {
                    $banner_image->delete();
                }
            }

            $storage_path = 'banner-images/';
            $banner_image = $request->file('banner');
            $banner_image_file_name = 'banner-image-' . $classroom->id . '-' . time() . '.' . $banner_image->getClientOriginalExtension();
            $banner_image_file_path =  $storage_path . $banner_image_file_name;

            $localfolder = public_path('firebase-temp-uploads') . '/';

            if ($banner_image->move($localfolder, $banner_image_file_name)) {
                $uploadedfile = fopen($localfolder . $banner_image_file_name, 'r');

                $bucket->upload($uploadedfile, ['name' => $banner_image_file_path]);

                unlink($localfolder . $banner_image_file_name);

                $classroom->update([
                    'banner_image_file_path' => $banner_image_file_path
                ]);
            } else {
                abort(500);
            }

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
        $bucket = app('firebase.storage')->getBucket();

        if ($classroom->banner_image_file_path != NULL) {
            $banner_image = $bucket->object($classroom->banner_image_file_path);
            if ($banner_image->exists()) {
                $banner_image->delete();
            }
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
