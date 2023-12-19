<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnswerController extends Controller
{
    public function store(Request $request, Question $question)
    {
        $validator = Validator::make($request->all(), [
            'answer' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $answer = $question->answers()->create([
            'answer' => $request->answer,
        ]);

        return response()->json($answer, 201);
    }

    public function update(Request $request, Question $question, Answer $answer)
    {
        $validator = Validator::make($request->all(), [
            'answer' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $answer->update([
            'answer' => $request->answer,
        ]);

        return response()->json($answer, 200);
    }

    public function delete(Question $question, Answer $answer)
    {
        $answer->delete();

        return response()->json('Answer deleted successfully', 200);
    }
}

