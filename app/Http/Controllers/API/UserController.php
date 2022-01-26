<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;

class UserController extends Controller
{
    // get user details
    public function index()
    {
        return response([
            'user' => User::with('attendedClasses')->find(Auth::id())
        ], 200);
    }

    // update user
    public function update(Request $request)
    {
        $user = $request->user();

        $attrs = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|email:dns|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'required|string',
            'date_of_birth' => 'required',
            'gender' => 'required',
        ]);

        $user->update([
            'name' => $attrs['name'],
            'email' => $attrs['email'],
            'phone_number' => $attrs['phone_number'],
            'date_of_birth' =>  Carbon::createFromFormat('Y-m-d', $attrs['date_of_birth'])->format('Y-m-d'),
            'gender' => $attrs['gender']
        ]);

        if (request()->hasFile('profile')) {
            $bucket = app('firebase.storage')->getBucket();

            if ($user->profile_image_file_path != NULL) {
                $profile_image = $bucket->object($user->profile_image_file_path);
                if ($profile_image->exists()) {
                    $profile_image->delete();
                }
            }

            $storage_path = 'profile-images/';
            $profile_image = $request->file('profile');
            $profile_image_file_name = 'profile-image-' . $user->id . '-' . time() . '.' . $profile_image->getClientOriginalExtension();
            $profile_image_file_path =  $storage_path . $profile_image_file_name;

            $localfolder = public_path('firebase-temp-uploads') . '/';

            if ($profile_image->move($localfolder, $profile_image_file_name)) {
                $uploadedfile = fopen($localfolder . $profile_image_file_name, 'r');

                $bucket->upload($uploadedfile, ['name' => $profile_image_file_path]);

                unlink($localfolder . $profile_image_file_name);  

                $user->update([
                    'profile_image_file_path' => $profile_image_file_path
                ]);
            } else {
                abort(500);
            }
            
            $user->update([
                'profile_image_file_path' => $profile_image_file_path
            ]);
        }

        return response([
            'message' => 'User updated.',
            'user' => User::with('attendedClasses')->find(Auth::id())
        ], 200);
    }
}
