<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User_info;
use App\Models\User;
use App\Http\Resources\UserinfoResource;
use App\Models\Role;

class UserinfoController extends Controller
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
            $data = User_info::whereHas('user', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })->with('user')->latest()->get();
        } else if ($user->role->name === Role::USER) {
            $data = User_Info::where('user_id', $user->user_id)->with('user')->latest()->get();
        } else if ($user->role->name === Role::ADMIN) {
            $data = User_Info::with('user')->latest()->get();
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

        // Check if the user already has a user_info record
        if ($user->user_info) {
            return response()->json(['error' => 'User already has a user info record'], 400);
        }

        // Check if the authenticated user is a company
        if ($user->role->name === Role::COMPANY) {
            return response()->json(['error' => 'Company users are not allowed to create user info records'], 403);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required',
            'dob' => 'required',
            'gender' => 'required|string|max:255',
            'phonenumber' => 'required|string|max:255',
            'house_type' => 'required|string|max:255',
            'house_number' => 'required|string|max:255',
            'street_number' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Check if the user_info record already exists for the user
        $existingUserInfo = User_info::where('user_id', $user->user_id)->first();
        if ($existingUserInfo) {
            return response()->json(['error' => 'User already has a user info record'], 400);
        }

        $user = auth()->user();

        $userinfo = User_info::create([
            'user_id' => $user->user_id, // Associate the user ID
            'image_cid' => $request->image,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'phonenumber' => $request->phonenumber,
            'house_type' => $request->house_type,
            'house_number' => $request->house_number,
            'street_number' => $request->street_number,
        ]);


        return response()->json($userinfo, 200);
        // return response()->json(['message' => 'User Info created successfully', 'userinfo' => new UserinfoResource($userinfo)]);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $userinfo = User_info::where('user_id', $id)->with('user')->get()->firstOrFail();
        if (is_null($userinfo)) {
            return response()->json('Data not found', 404);
        }

        // Check if the authenticated user is the owner of the form
        $user = auth()->user();
        if ($user->role->name === Role::COMPANY && $userinfo->user->company_id !== $user->company_id) {
            return response()->json('This user is not in your company record', 403);
        }
    
        if ($user->role->name !== Role::COMPANY && $user->user_id !== $userinfo->user_id) {
            return response()->json('You are not authorized to view this user', 403);
        }

        return response($userinfo, 200);
    }

    public function logged_user_info()
    {
        $user_id = auth()->user()->user_id;
        $userinfo = User_info::where('user_id', $user_id)->with('user')->get()->firstOrFail();
        return response([
            'user' => $userinfo,
            'message' => 'Logged User Data',
            'status' => 'success'
        ], 200);
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
        // Retrieve the existing User_info record
        $userinfo = User_info::find($id);

        // Check if the authenticated user is belongs to the company specified in the user table
        if ($userinfo->user->company_id !== $user->company_id) {
            return response()->json('You are not authorized to update other company user', 403);
        }

        // Check if the authenticated user is the owner of the user info or a company
        if ($user->user_id !== $userinfo->user_id && $user->role->name !== Role::COMPANY) {
            return response()->json('You are not authorized to update this user info', 403);
        }


        if (!$userinfo) {
            return response()->json('User info not found', 404);
        }

        $userinfo->image_cid = $request->image_cid;
        $userinfo->dob = $request->dob;
        $userinfo->gender = $request->gender;
        $userinfo->phonenumber = $request->phonenumber;
        $userinfo->house_type = $request->house_type;
        $userinfo->house_number = $request->house_number;
        $userinfo->street_number = $request->street_number;
        $userinfo->save();


        // Update the user record
        $userTable = User::where('user_id', $userinfo->user_id)->first();
        if (!$userTable) {
            return response()->json('User not found', 404);
        }

        if ($request->has('user.fullname') && $request->has('user.company_id')) {
            $userTable->fullname = $request->input('user.fullname');
            $userTable->company_id = $request->input('user.company_id');
            $userTable->save();
        } else if ($request->has('user.company_id')) {
            $userTable->company_id = $request->input('user.company_id');
            $userTable->save();
        } else if ($request->has('user.fullname')) {
            $userTable->fullname = $request->input('user.fullname');
            $userTable->save();
        }

        $userinfo = User_info::where('user_id', $userinfo->user_id)->with('user')->get()->firstOrFail();

        return response($userinfo, 200);
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
        // Retrieve the existing User_info record
        $userinfo = User_Info::find($id);

        if (!$userinfo) {
            return response()->json('User info not found', 404);
        }

        // Check if the authenticated user is belongs to the company specified in the user table
        if ($userinfo->user->company_id !== $user->company_id) {
            return response()->json('You are not authorized to delete other company user', 403);
        }
        // Check if the authenticated user is the owner of the user info or a company
        if ($user->user_id !== $userinfo->user_id && $user->role->name !== Role::COMPANY) {
            return response()->json('You are not authorized to delete this user info', 403);
        }

        $userinfo->delete();

        return response()->json('User info deleted successfully');
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

        $query = User_Info::query();

        // Add your search criteria based on the user's role
        if ($user->role->name === Role::ADMIN) {
            // Admin can search all data
            $query->where(function ($innerQuery) use ($keyword) {
                $innerQuery->where('user_id', 'like', "%$keyword%")
                ->orWhere('dob', 'like', "%$keyword%")
                ->orWhere('gender', 'like', "%$keyword%")
                ->orWhere('phonenumber', 'like', "%$keyword%")
                ->orWhere('house_type', 'like', "%$keyword%")
                ->orWhere('house_number', 'like', "%$keyword%")
                ->orWhere('street_number', 'like', "%$keyword%");
            });
        } elseif ($user->role->name === Role::COMPANY) {
            // Company can search only for the user data in their company
            $query->whereHas('user', function ($innerQuery) use ($user, $keyword) {
                $innerQuery->where('company_id', $user->company_id)
                    ->where(function ($subQuery) use ($keyword) {
                        $subQuery->where('user_id', 'like', "%$keyword%")
                        ->orWhere('dob', 'like', "%$keyword%")
                        ->orWhere('gender', 'like', "%$keyword%")
                        ->orWhere('phonenumber', 'like', "%$keyword%")
                        ->orWhere('house_type', 'like', "%$keyword%")
                        ->orWhere('house_number', 'like', "%$keyword%")
                        ->orWhere('street_number', 'like', "%$keyword%");
                    });
            });
        } elseif ($user->role->name === Role::USER) {
            // User can search only for their own data
            $query->where('user_id', $user->user_id)
                ->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->wherewhere('user_id', 'like', "%$keyword%")
                    ->orWhere('dob', 'like', "%$keyword%")
                    ->orWhere('gender', 'like', "%$keyword%")
                    ->orWhere('phonenumber', 'like', "%$keyword%")
                    ->orWhere('house_type', 'like', "%$keyword%")
                    ->orWhere('house_number', 'like', "%$keyword%")
                    ->orWhere('street_number', 'like', "%$keyword%");
                });

        }
        $results = $query->get();


        // Add your search criteria based on your needs
        $query->where('user_id', auth()->user()->user_id)
            ->where(function ($innerQuery) use ($keyword) {
                $innerQuery->where('dob', 'like', "%$keyword%")
                    ->orWhere('gender', 'like', "%$keyword%")
                    ->orWhere('phonenumber', 'like', "%$keyword%")
                    ->orWhere('house_type', 'like', "%$keyword%")
                    ->orWhere('house_number', 'like', "%$keyword%")
                    ->orWhere('street_number', 'like', "%$keyword%");
            });
        $results = $query->get();

        if ($results->isEmpty()) {
            return response()->json('No data found.', 404);
        }

        return response()->json($results);
    }
}
