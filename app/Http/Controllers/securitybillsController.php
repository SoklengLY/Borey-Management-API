<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User_info;
use App\Models\securitybills;
use App\Models\Role;
use App\Models\User;

class securitybillsController extends Controller
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
            $data = securitybills::whereHas('user', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })->latest()->get();
        } else if ($user->role->name === Role::ADMIN) {
            $data = securitybills::with('user.companies')->latest()->get();
        } else if ($user->role->name === Role::USER) {
            $data = securitybills::where('user_id', $user->user_id)->with('user')->latest()->get();
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
        if ($user->role->name !== Role::COMPANY) {
            return response()->json(['error' => 'Only Company can create water bill invoices!'], 403);
        }
        
        $validator = Validator::make($request->all(),[
            'user_id' => 'required',
            'category' => 'required',
            'price' => 'required',
            'payment_deadline' => 'required',
            'payment_status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $requestedUser = User::where('user_id', $request->user_id)->where('company_id', $user->company_id)->first();

        if (!$requestedUser) {
            return response()->json(['error' => 'Requested user does not belong to your company'], 403);
        }
        
        
        $user = auth()->user();

        $userInfo = User_Info::where('user_id', $request->user_id)->first();
        $userBaseInfo = User::where('user_id', $request->user_id)->first();

        if ($userBaseInfo->fullname === null) {
            return response()->json(['error' => 'User not found'], 405);
        }

        if ($userInfo->house_number === null || $userInfo->street_number === null || $userInfo->phonenumber === null) {
            return response()->json(['error' => 'User is missing information, cannot create!'], 403);
        }

        $securitybills = securitybills::create([
            'user_id' => $userInfo->user_id, // Associate the user ID
            'fullname' => $userBaseInfo->fullname,
            'phonenumber' => $userInfo->phonenumber, // Retrieve the value from the user info
            'house_number' => $userInfo->house_number, // Retrieve the value from the user info
            'street_number' => $userInfo->street_number, // Retrieve the value from the user info
            'payment_deadline' => $request->payment_deadline,
            'category' => $request->category,
            'price' => $request->price,
            'payment_status' => $request->payment_status,
            'paid_date' => $request->paid_date,
        ]);
        
        return response($securitybills, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $securitybills = securitybills::find($id);
        if (is_null($securitybills)) {
            return response()->json('Security Bill not found', 404); 
        }

        // Check if the authenticated user is the owner of the form
        $user = auth()->user();
        if ($user->role->name === Role::COMPANY && $securitybills->user->company_id !== $user->company_id) {
            return response()->json('This bill is not in your company record', 403);
        }
    
        if ($user->role->name !== Role::COMPANY && $user->user_id !== $securitybills->user_id) {
            return response()->json('You are not authorized to view this bill', 403);
        }

        return response()->json($securitybills, 200);
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
        $securitybills = securitybills::find($id);

        if (!$securitybills) {
            return response()->json('Water Bill not found', 404);
        }

        // Check if the authenticated user is belongs to the company specified in the user table
        if ($securitybills->user->company_id !== $user->company_id) {
            return response()->json('You are not authorized to update other company bill', 403);
        }
        // Check if the authenticated user is the owner of the user info
        if ($user->user_id !== $securitybills->user_id && $user->role->name !== Role::COMPANY ) {
            return response()->json('You are not authorized to update this bill', 403);
        }

        if ($user->role->name === Role::COMPANY) {
            $validator = Validator::make($request->all(), [
                'category' => 'required',
                'payment_deadline' => 'required',
                'price' => 'required',
                'payment_status' => 'required',
            ]);


            if ($validator->fails()) {
                return response()->json($validator->errors());
            }


            // Updating the electric bill form with the request data
            $securitybills->category = $request->category;
            $securitybills->payment_deadline = $request->payment_deadline;
            $securitybills->price = $request->price;
            $securitybills->payment_status = $request->payment_status;

            // Saving the updated electric bill form
            $securitybills->save();

            // Returning the response
            return response($securitybills, 200);
        } elseif ($user->role->name === Role::USER) {
            $validator = Validator::make($request->all(), [
                'payment_status' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            // Updating the electric bill form with the request data
            $securitybills->paid_date = now();
            $securitybills->payment_status = $request->payment_status;

            // Saving the updated electric bill form
            $securitybills->save();
    
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
    
            // Updating the electric bill form with the request data
            $securitybills->paid_date = now();
            $securitybills->payment_status = $request->payment_status;
    
            // Saving the updated electric bill form
            $securitybills->save();
    
            // Returning the response
            return response($securitybills, 200);
        } else {
            return response()->json('You are not authorized to update this bill', 403);
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

        $securitybills = securitybills::find($id);

        // Check if the authenticated user is belongs to the company specified in the user table
        if ($securitybills->user->company_id !== $user->company_id) {
            return response()->json('You are not authorized to delete other company bill', 403);
        }

        if ($user->user_id !== $securitybills->user_id && $user->role->name !== Role::COMPANY) {
        // User is not authorized to delete this form
        return response()->json('You are not authorized to delete this bill', 403);
        }

        $securitybills->delete();

        return response()->json('Bill deleted successfully');
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

        $query = securitybills::query();

        // Add your search criteria based on the user's role
        if ($user->role->name === Role::ADMIN) {
            // Admin can search all data
            $query->where(function ($innerQuery) use ($keyword) {
                $innerQuery->where('user_id', 'like', "%$keyword%")
                    ->orWhere('fullname', 'like', "%$keyword%")
                    ->orWhere('phonenumber', 'like', "%$keyword%")
                    ->orWhere('house_number', 'like', "%$keyword%")
                    ->orWhere('street_number', 'like', "%$keyword%")
                    ->orWhere('category', 'like', "%$keyword%")
                    ->orWhere('paid_date', 'like', "%$keyword%")
                    ->orWhere('payment_deadline', 'like', "%$keyword%")
                    ->orWhere('price', 'like', "%$keyword%")
                    ->orWhere('payment_status', 'like', "%$keyword%");
            });
        } elseif ($user->role->name === Role::COMPANY) {
            // Company can search only for the user data in their company
            $query->whereHas('user', function ($innerQuery) use ($user, $keyword) {
                $innerQuery->where('company_id', $user->company_id)
                    ->where(function ($subQuery) use ($keyword) {
                        $subQuery->where('username', 'like', "%$keyword%")
                            ->orWhere('fullname', 'like', "%$keyword%")
                            ->orWhere('phonenumber', 'like', "%$keyword%")
                            ->orWhere('house_number', 'like', "%$keyword%")
                            ->orWhere('street_number', 'like', "%$keyword%")
                            ->orWhere('category', 'like', "%$keyword%")
                            ->orWhere('date_payment', 'like', "%$keyword%")
                            ->orWhere('price', 'like', "%$keyword%")
                            ->orWhere('payment_status', 'like', "%$keyword%");
                    });
            });
        } elseif ($user->role->name === Role::USER) {
            // User can search only for their own data
            $query->where('user_id', $user->user_id)
                ->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('username', 'like', "%$keyword%")
                        ->orWhere('fullname', 'like', "%$keyword%")
                        ->orWhere('phonenumber', 'like', "%$keyword%")
                        ->orWhere('house_number', 'like', "%$keyword%")
                        ->orWhere('street_number', 'like', "%$keyword%")
                        ->orWhere('category', 'like', "%$keyword%")
                        ->orWhere('date_payment', 'like', "%$keyword%")
                        ->orWhere('price', 'like', "%$keyword%")
                        ->orWhere('payment_status', 'like', "%$keyword%");
                });

        }

        $results = $query->get();

        if ($results->isEmpty()) {
            return response()->json('No data found.', 404);
        }

        return response()->json($results);
    }
}
