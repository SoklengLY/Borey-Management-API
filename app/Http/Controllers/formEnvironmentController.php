<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\formEnvironment;
use App\Models\User;
use App\Models\Role;



class formEnvironmentController extends Controller
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
            $data = formEnvironment::whereHas('user', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })->with('user')->latest()->get();
        } else if ($user->role->name === Role::USER) {
            $data = formEnvironment::where('user_id', $user->user_id)->latest()->get();
        } else if ($user->role->name === Role::ADMIN) {

            $data = formEnvironment::with('user.companies')->latest()->get();
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
            'environment_status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $username = $user->username;
        $fullname = $user->fullname;
        $email = $user->email;

        $formEnvironment = formEnvironment::create([
            'user_id' => $user->user_id, // Associate the user ID
            'username' => $username,
            'fullname' => $fullname,
            'email' => $email,
            'category' => $request->category,
            'problem_description' => $request->problem_description,
            'path' => $request->image,
            'environment_status' => $request->environment_status,
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
        $formEnvironment = formEnvironment::find($id);
        if (is_null($formEnvironment)) {
            return response()->json('Form not found', 404);
        }

        // Check if the authenticated user is the owner of the form
        $user = auth()->user();
        if ($user->role->name === Role::COMPANY && $formEnvironment->user->company_id !== $user->company_id) {
            return response()->json('This form is not in your company record', 403);
        }
    
        if ($user->role->name !== Role::COMPANY && $user->user_id !== $formEnvironment->user_id) {
            return response()->json('You are not authorized to view this form', 403);
        }

        return response()->json($formEnvironment, 200);
        
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

        $formEnvironment = formEnvironment::find($id);

        if (!$formEnvironment) {
            return response()->json('Form not found', 404);
        }

        // Check if the authenticated user is belongs to the company specified in the user table
        if ($formEnvironment->user->company_id !== $user->company_id) {
            return response()->json('You are not authorized to update other company form', 403);
        }
        
        // Check if the authenticated user is the owner of the user info
        if ($user->user_id !== $formEnvironment->user_id && $user->role->name !== Role::COMPANY ) {
            return response()->json('You are not authorized to update this form', 403);
        }

        // Check if the authenticated user is the owner of the form
        if ($user->role->name === Role::COMPANY) {
            $validator = Validator::make($request->all(), [
                'environment_status' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
            $formEnvironment->user_id;
            $formEnvironment->username;
            $formEnvironment->fullname;
            $formEnvironment->email;
            $formEnvironment->category;
            $formEnvironment->problem_description;
            $formEnvironment->path;
            $formEnvironment->environment_status = $request->environment_status; // Update the environment_status value

            $formEnvironment->save();

            return response($formEnvironment, 200);
        } else if ($user->role->name === Role::USER) {
            $validator = Validator::make($request->all(), [
                'category' => 'required|string|max:255',
                'problem_description' => 'required',
                'path' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            $formEnvironment->user_id;
            $formEnvironment->username;
            $formEnvironment->fullname;
            $formEnvironment->email;
            $formEnvironment->category = $request->category;
            $formEnvironment->problem_description = $request->problem_description;
            $formEnvironment->path = $request->path;
            $formEnvironment->environment_status = $request->environment_status; // Update the environment_status value

            $formEnvironment->save();

            return response($formEnvironment, 200);
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
        $formEnvironment = formEnvironment::find($id);

        // Check if the authenticated user is belongs to the company specified in the user table
        if ($formEnvironment->user->company_id !== $user->company_id) {
            return response()->json('You are not authorized to delete other company form', 403);
        }

        if ($user->user_id !== $formEnvironment->user_id && $user->role->name !== Role::COMPANY) {
        // User is not authorized to delete this form
        return response()->json('You are not authorized to delete this form', 403);
        }
        $formEnvironment->delete();

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

        $query = formEnvironment::query();

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
                    ->orWhere('environment_status', 'like', "%$keyword%");
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
                        ->orWhere('environment_status', 'like', "%$keyword%");
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
                    ->orWhere('environment_status', 'like', "%$keyword%");
                });

        }
        $results = $query->get();

        if ($results->isEmpty()) {
            return response()->json('No data found.', 404);
        }

        return response()->json($results);
    }
}
