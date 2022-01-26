<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
    public function index($assignmentId)
    {
        $assignment = Assignment::find($assignmentId)->with('submissions')->with('submissions.user')->with('submissionHistories', function ($query) {
            return $query->with('submission')->where('user_id', Auth::id())->orderBy('created_at', 'DESC');
        })->first();

        return response([
            'submissions' => $assignment['submissions'],
            'submissionHistories' => SubmissionHistory::where([['assignment_id', $assignmentId], ['user_id', Auth::id()]])->get()
        ], 200);
    }

    public function store(Request $request, $assignmentId)
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

                unlink($localfolder . $submission_file_name);  
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

        return response([
            'submission' => $submission
        ], 200);
    }

    public function show($submissionId)
    {
        return response([
            'submission' => Submission::find($submissionId)
        ], 200);
    }

    public function destroy($submissionId)
    {
        $submission = Submission::find($submissionId);
        
        $bucket = app('firebase.storage')->getBucket();

        if ($submission->submission_file_path != NULL) {
            $file = $bucket->object($submission->submission_file_path);
            if ($file->exists()) {
                $file->delete();
            }
        }

        $submission->delete();

        return response([
            'message' => 'Submission deleted.',
        ], 200);
    }
}
