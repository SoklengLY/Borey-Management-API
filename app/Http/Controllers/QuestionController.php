<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;

class QuestionController extends Controller
{
    public function index(Request $request, $surveyId)
    {
        $user = auth()->user();
        $survey = Survey::findOrFail($surveyId);

        // Check if the authenticated user has the USER or COMPANY role and belongs to the same company as the survey
        if (($user->role->name === Role::USER || $user->role->name === Role::COMPANY) && $user->company_id === $survey->company_id) {
            // Retrieve questions for the specified survey
            $questions = Question::where('survey_id', $surveyId)->get();

            return response()->json($questions, 200);
        }

        return response()->json('You are not authorized to view these questions', 403);
    }

    public function show($surveyId, $questionId)
    {
        $user = auth()->user();
        $survey = Survey::findOrFail($surveyId);

        // Check if the authenticated user has the USER or COMPANY role and belongs to the same company as the survey
        if (($user->role->name === Role::USER || $user->role->name === Role::COMPANY) && $user->company_id === $survey->company_id) {
            // Find the question by ID, considering the specified survey ID
            $question = Question::where('survey_id', $surveyId)->findOrFail($questionId);

            return response()->json($question, 200);
        }

        return response()->json('You are not authorized to view this question', 403);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Check if the authenticated user has the COMPANY role
        if ($user->role->name !== Role::COMPANY) {
            return response()->json(['error' => 'Only COMPANY can create questions.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'survey_id' => 'required|exists:surveys,id,company_id,' . $user->company_id,
            'question' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $question = Question::create($request->all());

        return response()->json($question, 201);
    }

    public function update(Request $request, Question $question)
    {
        $user = auth()->user();

        // Check if the authenticated user has the COMPANY role and the question belongs to their company
        if ($user->role->name !== Role::COMPANY || $question->survey->company_id !== $user->company_id) {
            return response()->json(['error' => 'You are not authorized to update this question.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'survey_id' => 'exists:surveys,id,company_id,' . $user->company_id,
            'question' => 'required',
            'type' => 'required|in:text,mcq',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $question->update($request->all());

        return response()->json($question, 200);
    }

    public function delete(Question $question)
    {
        $user = auth()->user();

        // Check if the authenticated user has the COMPANY role and the question belongs to their company
        if ($user->role->name !== Role::COMPANY || $question->survey->company_id !== $user->company_id) {
            return response()->json(['error' => 'You are not authorized to delete this question.'], 403);
        }

        $question->delete();

        return response()->json('Question deleted successfully', 200);
    }
}
