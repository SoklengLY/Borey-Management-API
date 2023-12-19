<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\formGeneral;
use App\Models\User;
use App\Models\Role;


class formGeneralController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();

        // Check if the authenticated user is a company
        if ($user->role->name === Role::COMPANY) {
            $data = formGeneral::whereHas('user', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })->with('user')->latest()->get();
        } else if ($user->role->name === Role::USER){
            $data = formGeneral::where('user_id', $user->user_id)->latest()->get();
        } else if ($user->role->name === Role::ADMIN) {
            $data = formGeneral::with('user.companies')->latest()->get();
        }

        return response($data, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Check if the authenticated user is a company
        if ($user->role->name === Role::COMPANY) {
            return response()->json(['error' => 'Company users are not allowed to create the records'], 403);
        }

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'problem_description' => 'required',
            'image' => 'required',
            'general_status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $username = $user->username;
        $fullname = $user->fullname;
        $email = $user->email;

        $formEnvironment = formGeneral::create([
            'user_id' => $user->user_id, // Associate the user ID
            'username' => $username,
            'fullname' => $fullname,
            'email' => $email,
            'category' => $request->category,
            'problem_description' => $request->problem_description,
            'path' => $request->image,
            'general_status' => $request->general_status,
        ]);

        return response($formEnvironment, 200);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $formGeneral = formGeneral::find($id);
        if (is_null($formGeneral)) {
            return response()->json('Data not found', 404);
        }

        // Check if the authenticated user is the owner of the form
        $user = auth()->user();
        if ($user->role->name === Role::COMPANY && $formGeneral->user->company_id !== $user->company_id) {
            return response()->json('This form is not in your company record', 403);
        }
    
        if ($user->role->name !== Role::COMPANY && $user->user_id !== $formGeneral->user_id) {
            return response()->json('You are not authorized to view this form', 403);
        }

        return response()->json($formGeneral, 200);
        // return response()->json([new FormGeneralResource($formGeneral)]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $user = auth()->user();

        $formGeneral = formGeneral::find($id);

        if (!$formGeneral) {
            return response()->json('Form not found', 404);
        }

        // Check if the authenticated user is belongs to the company specified in the user table
        if ($formGeneral->user->company_id !== $user->company_id) {
            return response()->json('You are not authorized to update other company form', 403);
        }
        
        // Check if the authenticated user is the owner of the user info
        if ($user->user_id !== $formGeneral->user_id && $user->role->name !== Role::COMPANY ) {
            return response()->json('You are not authorized to update this form', 403);
        }

        // Check if the authenticated user is the owner of the form
        if ($user->role->name === Role::COMPANY) {
            $validator = Validator::make($request->all(), [
                'general_status' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
            $formGeneral->user_id;
            $formGeneral->username;
            $formGeneral->fullname;
            $formGeneral->email;
            $formGeneral->category;
            $formGeneral->problem_description;
            $formGeneral->path;
            $formGeneral->general_status = $request->general_status; // Update the environment_status value

            $formGeneral->save();

            return response($formGeneral, 200);
        } else if ($user->role->name === Role::USER) {
            $validator = Validator::make($request->all(), [
                'category' => 'required|string|max:255',
                'problem_description' => 'required',
                'path' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            $formGeneral->user_id;
            $formGeneral->username;
            $formGeneral->fullname;
            $formGeneral->email;
            $formGeneral->category = $request->category;
            $formGeneral->problem_description = $request->problem_description;
            $formGeneral->path = $request->path;
            $formGeneral->general_status = $request->general_status; // Update the environment_status value

            $formGeneral->save();

            return response($formGeneral, 200);
        } else {
            return response()->json('You are not authorized to update this form', 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $formGeneral = formGeneral::find($id);

        // Check if the authenticated user is belongs to the company specified in the user table
        if ($formGeneral->user->company_id !== $user->company_id) {
            return response()->json('You are not authorized to delete other company form', 403);
        }

        if ($user->user_id !== $formGeneral->user_id && $user->role->name !== Role::COMPANY) {
        // User is not authorized to delete this form
        return response()->json('You are not authorized to delete this form', 403);
        }
        $formGeneral->delete();

        return response()->json('Form deleted successfully');
    }

    /**
     * Search user info records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $user = auth()->user();
        $keyword = $request->input('keyword');

        $query = formGeneral::query();

       // Add your search criteria based on the user's role
       if ($user->role->name === Role::ADMIN) {
            // Admin can search all data
            $query->where(function ($innerQuery) use ($keyword) {
                $innerQuery->where('user_id', 'like', "%$keyword%")
                    ->orWhere('username', 'like', "%$keyword%")
                    ->orWhere('fullname', 'like', "%$keyword%")
                    ->orWhere('email', 'like', "%$keyword%")
                    ->orWhere('category', 'like', "%$keyword%")
                    ->orWhere('problem_description', 'like', "%$keyword%")
                    ->orWhere('general_status', 'like', "%$keyword%");
            });
        } elseif ($user->role->name === Role::COMPANY) {
            // Company can search only for the user data in their company
            $query->whereHas('user', function ($innerQuery) use ($user, $keyword) {
                $innerQuery->where('company_id', $user->company_id)
                    ->where(function ($subQuery) use ($keyword) {
                        $subQuery->where('user_id', 'like', "%$keyword%")
                        ->orWhere('username', 'like', "%$keyword%")
                        ->orWhere('fullname', 'like', "%$keyword%")
                        ->orWhere('email', 'like', "%$keyword%")
                        ->orWhere('category', 'like', "%$keyword%")
                        ->orWhere('problem_description', 'like', "%$keyword%")
                        ->orWhere('general_status', 'like', "%$keyword%");
                    });
            });
        } elseif ($user->role->name === Role::USER) {
            // User can search only for their own data
            $query->where('user_id', $user->user_id)
                ->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->wherewhere('user_id', 'like', "%$keyword%")
                    ->orWhere('username', 'like', "%$keyword%")
                    ->orWhere('fullname', 'like', "%$keyword%")
                    ->orWhere('email', 'like', "%$keyword%")
                    ->orWhere('category', 'like', "%$keyword%")
                    ->orWhere('problem_description', 'like', "%$keyword%")
                    ->orWhere('general_status', 'like', "%$keyword%");
                });

        }
        $results = $query->get();

        if ($results->isEmpty()) {
            return response()->json('No data found.', 404);
        }

        return response()->json($results);
    }
}
