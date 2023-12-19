<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\Role;
use App\Models\User_info;


class UserController extends Controller
{

    public function register(Request $request){
        $request->validate([
            'username' => 'required',
            'fullname' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
            'company_id' => 'required',
            ]);

        if(User::where('email', $request->email)->first()){
            return response([
                'message' => 'Email already exists',
                'status' => 'failed'
            ], 200);
        }

        $user = User::create([
            'username' => $request->username,
            'fullname' => $request->fullname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $request->company_id,
            'date_registered' => now(), // Set the current date and time
        ]);

        // Retrieve the desired role from the database
        $role = Role::where('name', 'user')->first();

        // Assign the role to the user
        $user->role_id = $role->id;
        $user->save();
        
        $userInfo = User_info::create([
            'user_id' => $user->user_id, // Associate the user ID
            'image_cid' => $request->image_cid,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'phonenumber' => $request->phonenumber,
            'house_type' => $request->house_type,
            'house_number' => $request->house_number,
            'street_number' => $request->street_number,
            'created_at' => now()
        ]);

        $userInfo->save();

        $token = $user->createToken($request->email)->plainTextToken;
        return response([
            'token' => $token,
            'message' => 'Registration Success',
            'status' => 'success'
        ], 201);
    }

    public function login(Request $request){
        $request->validate([
            'email'=>'required|email',
            'password'=>'required',
        ]);
        $user = User::where('email', $request->email)->first();
        if($user && Hash::check($request->password, $user->password)){
            $token = $user->createToken($request->email)->plainTextToken;
            return response([
                'token'=>$token,
                'message' => 'Login Success',
                'status'=>'success'
            ], 200);
        }
        return response([
            'message' => 'The Provided Credentials are incorrect',
            'status'=>'failed'
        ], 401);
    }

    public function logout(){
        if (auth()->user()) {
            auth()->user()->tokens()->delete();

            return response([
                'message' => 'Logout Success',
                'status' => 'success'
            ], 200);
        }

        return response([
            'message' => 'User not authenticated',
            'status' => 'failed'
        ], 401);
    }
    
    public function logged_user(){
        $loggeduser = auth()->user();
        return response([
            'user'=>$loggeduser,
            'message' => 'Logged User Data',
            'status'=>'success'
        ], 200);
    }

    public function change_password(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed',
        ]);
        $loggeduser = auth()->user();

        if ($loggeduser && Hash::check($request->current_password, $loggeduser->password)) {

            $loggeduser->password = Hash::make($request->password);
            $loggeduser->save();
            return response([
                'message' => 'Password Changed Successfully',
                'status' => 'success'
            ], 200);
        } else {
            return response([
                'message' => 'Password Does Not Match',
                'status' => 'fail'
            ], 401);
        }
    }

    public function destroy($id)
    {

        // Retrieve the existing User_info record
        $userNow = User::find($id);

        if (is_null($userNow)) {
            return response()->json('User not found', 404);
        }

        // Check if the authenticated user is the owner of the user info or a company
        $user = auth()->user();
        if ($user->role->name !== Role::COMPANY) {
            return response()->json('You are not authorized to delete this user info', 403);
        }

        $userNow->delete();

        return response()->json('User info deleted successfully');
    }

}
