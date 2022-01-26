<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Classroom;
use App\Models\ClassAttendee;

class DashboardController extends Controller
{
    public function create()
    {
        return Inertia::render('Dashboard', [
            'user' => User::with('attendedClasses')->find(Auth::id())
        ]);
    }

    public function createClassroom(Request $request)
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
        }

        return redirect()->back()->with('message', 'Class created.');;
    }

    public function joinClassroom(Request $request)
    {
        $attrs = $request->validate([
            'classroom_id' => 'required|string',
            'invite_code' => 'required|string|max:255',
        ]);

        $classroom = Classroom::find($attrs['classroom_id']);

        if ($classroom != null) {
            if ($classroom->invite_code == $attrs['invite_code']) {
                ClassAttendee::create([
                    'user_id' => Auth::id(),
                    'classroom_id' => $classroom->id,
                    'role' => 'Student'
                ]);

                return redirect()->back()->with('message', 'Class joined.');;
            }
        }
    }
}
