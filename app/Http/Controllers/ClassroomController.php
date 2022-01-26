<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Classroom;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use App\Models\Material;
use Inertia\Inertia;
use Carbon\Carbon;

class ClassroomController extends Controller
{
    public function create($id)
    {
        return Inertia::render('Classroom', [
            'classroom' => Classroom::with('classAttendees')->with('assignments')->with('assignments.submissions')->with('materials')->find($id),
            'assignments' => Assignment::with('submissions')->with('submissions.user')->with('submissionHistories', function ($query) {
                return $query->with('submission')->where('user_id', Auth::id())->orderBy('created_at', 'DESC');
            })->orderBy('created_at', 'DESC')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {

        $attrs = $request->validate([
            'name' => 'required|string',
            'invite_code' => 'required|string|max:255',
        ]);

        $classroom = Classroom::find($id);

        if ($classroom != null) {
            $classroom->update([
                'name' => $attrs['name'],
                'invite_code' => $attrs['invite_code'],
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

            return redirect()->back()->with('message', 'Classroom detail updated!');
        }
    }

    public function addAssignment(Request $request, $classroomId)
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

        return redirect()->back()->with('message', 'Assignment added!');
    }

    public function deleteAssignment($assignmentId)
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

        return redirect()->back()->with('message', 'Assignment deleted!');
    }

    public function addSubmission(Request $request, $assignmentId)
    {
        $attrs = $request->validate([
            'title' => 'required|max:255',
        ]);

        $submission = Submission::where('user_id', Auth::id())->where('assignment_id', $assignmentId)->first();

        $bucket = app('firebase.storage')->getBucket();
        $storage_path = 'submissions/';
        $submissionFile = $request->file('submission');
        $submission_file_name = 'submission-' . $attrs['title'] . '-' . time() . '.' . $submissionFile->getClientOriginalExtension();
        $submission_file_path =  $storage_path . $submission_file_name;
        $localfolder = public_path('firebase-temp-uploads') . '/';

        if ($submission == null) {
            if ($submissionFile->move($localfolder, $submission_file_name)) {
                $uploadedfile = fopen($localfolder . $submission_file_name, 'r');

                $bucket->upload($uploadedfile, ['name' => $submission_file_path]);

                unlink($localfolder . $submission_file_name);  
            } else {
                abort(500);
            }

            $submission = Submission::create([
                'title' => $attrs['title'],
                'submission_file_path' => $submission_file_path,
                'user_id' => Auth::id(),
                'assignment_id' => $assignmentId
            ]);
        } else {
            if ($submission->submission_file_path != NULL) {
                $file = $bucket->object($submission->submission_file_path);
                if ($file->exists()) {
                    $file->delete();
                }
            }

            if ($submissionFile->move($localfolder, $submission_file_name)) {
                $uploadedfile = fopen($localfolder . $submission_file_name, 'r');

                $bucket->upload($uploadedfile, ['name' => $submission_file_path]);
            } else {
                abort(500);
            }

            $submission->update([
                'title' => $attrs['title'],
                'submission_file_path' => $submission_file_path,
                'user_id' => Auth::id(),
                'assignment_id' => $assignmentId
            ]);
        }

        SubmissionHistory::create([
            'title' => $attrs['title'],
            'user_id' => Auth::id(),
            'assignment_id' => $assignmentId,
            'submission_id' => $submission->id,
        ]);

        return redirect()->back()->with('message', 'Submission added!');
    }

    public function addMaterial(Request $request, $classroomId)
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

        return redirect()->back()->with('message', 'Material added!');
    }

    public function deleteMaterial($materialId)
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

        return redirect()->back()->with('message', 'Material deleted!');
    }
}
