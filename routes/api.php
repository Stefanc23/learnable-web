<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ClassroomController;
use App\Http\Controllers\API\AssignmentController;
use App\Http\Controllers\API\SubmissionController;
use App\Http\Controllers\API\MaterialController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logoutall', [AuthController::class, 'logoutAll']);
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', [UserController::class, 'index']);
    Route::post('/user', [UserController::class, 'update']);

    Route::get('/classrooms', [ClassroomController::class, 'index']);
    Route::post('/classrooms', [ClassroomController::class, 'store']);
    Route::get('/classrooms/{classroomId}', [ClassroomController::class, 'show']);
    Route::put('/classrooms/{classroomId}', [ClassroomController::class, 'update']);
    Route::delete('/classrooms/{classroomId}', [ClassroomController::class, 'destroy']);
    Route::get('/classrooms/join/{classroomId}/{inviteCode}', [ClassroomController::class, 'join']);
    Route::get('/classrooms/{classroomId}/attendees', [ClassroomController::class, 'getAttendees']);

    Route::get('/classrooms/{classroomId}/assignments', [AssignmentController::class, 'index']);
    Route::post('/classrooms/{classroomId}/assignments', [AssignmentController::class, 'store']);
    Route::get('/assignments/{assignmentId}', [AssignmentController::class, 'show']);
    Route::delete('/assignments/{assignmentId}', [AssignmentController::class, 'destroy']);

    Route::get('/assignments/{assignmentId}/submissions', [SubmissionController::class, 'index']);
    Route::post('/assignments/{assignmentId}/submissions', [SubmissionController::class, 'store']);
    Route::post('/assignments/{assignmentId}/submissions/{userId}', [SubmissionController::class, 'getSubmissionHistory']);
    Route::get('/submissions/{submissionId}', [SubmissionController::class, 'show']);
    Route::delete('/submissions/{submissionId}', [SubmissionController::class, 'destroy']);

    Route::get('/classrooms/{classroomId}/materials', [MaterialController::class, 'index']);
    Route::post('/classrooms/{classroomId}/materials', [MaterialController::class, 'store']);
    Route::get('/materials/{materialId}', [MaterialController::class, 'show']);
    Route::delete('/materials/{materialId}', [MaterialController::class, 'destroy']);
});
