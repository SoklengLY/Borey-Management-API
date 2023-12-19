<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SurveyController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Check if the user has either the "USER" or "COMPANY" role
        if ($user->role->name === Role::USER || $user->role->name === Role::COMPANY) {
            $surveys = Survey::where('company_id', $user->company_id)->get();
            return response()->json($surveys, 200);
        }

        return response()->json('You are not authorized to access surveys', 403);
    }


    public function store(Request $request)
    {
        $user = auth()->user();

        // Check if the authenticated user is a company
        if ($user->role->name !== Role::COMPANY) {
            return response()->json(['error' => 'Only Company can create the surveys!'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $survey = Survey::create([
            'title' => $request->title,
            'description' => $request->description,
            'company_id' => $user->company_id,
        ]);

        return response()->json($survey, 201);
    }

    public function show($id)
    {
        $survey = Survey::with('questions.answers')->findOrFail($id);

        $user = auth()->user();
        Log::info('survey company ID: ' . $survey->company_id);

        // Check if the user has either the "USER" or "COMPANY" role and belongs to the same company as the survey
        if ($user->role->name === Role::USER && $user->company_id === $survey->company_id) {
            return response()->json($survey, 200);
        } else if ($user->role->name === Role::COMPANY && $user->company_id === $survey->company_id ) {
            return response()->json($survey, 200);
        }

        return response()->json('You are not authorized to view this survey', 403);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $survey = Survey::findOrFail($id);

        $user = auth()->user();
        if ($user->role->name !== Role::COMPANY || $user->company_id !== $survey->company_id) {
            return response()->json('You are not authorized to update this survey', 403);
        }

        $survey->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return response()->json($survey, 200);
    }

    public function destroy($id)
    {
        $survey = Survey::findOrFail($id);

        $user = auth()->user();
        if ($user->role->name !== Role::COMPANY || $user->company_id !== $survey->company_id) {
            return response()->json('You are not authorized to delete this survey', 403);
        }

        $survey->delete();

        return response()->json(['message' => 'Survey deleted successfully'], 200);
    }
}
