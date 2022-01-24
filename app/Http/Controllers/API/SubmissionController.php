<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        if ($submission == null) {
            $new_submission = $request->file('submission');
            $submission_file_path = 'submission-' . $attrs['title'] . '-' . time() . '.' . $new_submission->getClientOriginalExtension();
            $submission_file_path = $new_submission->storeAs('submissions', $submission_file_path);

            $submission = Submission::create([
                'title' => $attrs['title'],
                'submission_file_path' => $submission_file_path,
                'user_id' => Auth::id(),
                'assignment_id' => $assignmentId
            ]);
        } else {
            if (Storage::exists($submission->submission_file_path)) {
                Storage::delete($submission->submission_file_path);
            }

            $new_submission = $request->file('submission');
            $submission_file_path = 'submission-' . $attrs['title'] . '-' . time() . '.' . $new_submission->getClientOriginalExtension();
            $submission_file_path = $new_submission->storeAs('submissions', $submission_file_path);

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
        if (Storage::exists($submission->submission_file_path)) {
            Storage::delete($submission->submission_file_path);
        }
        $submission->delete();

        return response([
            'message' => 'Submission deleted.',
        ], 200);
    }
}
