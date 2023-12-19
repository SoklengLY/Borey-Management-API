<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\User_Info;
use App\Models\electricbills;
use App\Models\formEnvironment;
use App\Models\formGeneral;
use App\Models\Post;
use App\Models\Role;
use App\Models\Companies;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $electricBills = $this->searchElectricBill($request);
        $formGenerals = $this->searchFormGeneral($request);
        $posts = $this->searchPost($request);
        $formEnvironments = $this->searchFormEnvironment($request);

        $results = [
            'electricBills' => $electricBills,
            'formGenerals' => $formGenerals,
            'posts' => $posts,
            'formEnvironments' => $formEnvironments,
        ];

        return response()->json($results);
    }

    function searchElectricBill(Request $request)
    {
        $user = auth()->user();
        $keyword = $request->input('keyword');

        $query = electricbills::query();

        if ($user->role->name === Role::USER) {
            // User can search only for their own data
            $query->where('user_id', $user->user_id)
                ->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->whereRaw("LOWER(fullname) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(phonenumber) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(house_number) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(street_number) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(category) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(payment_deadline) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(price) LIKE ?", ['%' . strtolower($keyword) . '%']);
                });
        } else {
            $query->where(function ($innerQuery) use ($keyword) {
                $innerQuery->whereRaw("LOWER(fullname) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(phonenumber) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(house_number) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(street_number) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(category) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(payment_deadline) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(price) LIKE ?", ['%' . strtolower($keyword) . '%']);
            });
        }

        $results = $query->get();

        if ($results->count() === 0) {
            return response()->json('No data found.', 200);
        }

        return response()->json($results);
    }

    function searchFormGeneral(Request $request)
    {
        $user = auth()->user();
        $keyword = $request->input('keyword');

        $query = formGeneral::query();

        if ($user->role->name === Role::USER) {
            // User can search only for their own data
            $query->where('user_id', $user->user_id)
                ->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->whereRaw("LOWER(username) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(fullname) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(email) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(category) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(problem_description) LIKE ?", ['%' . strtolower($keyword) . '%']);
                });
        } else {
            $query->where(function ($innerQuery) use ($keyword) {
                $innerQuery->whereRaw("LOWER(username) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(fullname) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(email) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(category) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(problem_description) LIKE ?", ['%' . strtolower($keyword) . '%']);
            });
        }

        $results = $query->get();

        if ($results->count() === 0) {
            return response()->json('No data found.', 200);
        }

        return response()->json($results);
    }

    function searchPost(Request $request)
    {
        $user = auth()->user();
        $keyword = $request->input('keyword');

        $query = Post::query();

        if ($user->role->name === Role::USER) {
            // User can search only for their own data
            $query->where('user_id', $user->user_id)
                ->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->whereRaw("LOWER(heading) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(content_type) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(description) LIKE ?", ['%' . strtolower($keyword) . '%']);
                });
        } else {
            $query->where(function ($innerQuery) use ($keyword) {
                $innerQuery->whereRaw("LOWER(content_type) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(heading) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(description) LIKE ?", ['%' . strtolower($keyword) . '%']);
            });
        }

        $result = $query->orderByRaw("CASE WHEN LOWER(heading) LIKE ? THEN 0 ELSE 1 END", ['%' . strtolower($keyword) . '%'])
            ->with('user', 'comments.companies', 'comments.userInfo.user', 'userInfo', 'likes', 'shares')->get();

        if (!$result) {
            return response()->json('No data found.', 200);
        }

        return response()->json($result);
    }

    function searchFormEnvironment(Request $request)
    {
        $user = auth()->user();
        $keyword = $request->input('keyword');

        $query = formEnvironment::query();

        if ($user->role->name === Role::USER) {
            // User can search only for their own data
            $query->where('user_id', $user->user_id)
                ->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->whereRaw("LOWER(fullname) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(email) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(category) LIKE ?", ['%' . strtolower($keyword) . '%'])
                        ->orWhereRaw("LOWER(problem_description) LIKE ?", ['%' . strtolower($keyword) . '%']);
                });
        } else {
            $query->where(function ($innerQuery) use ($keyword) {
                $innerQuery->whereRaw("LOWER(fullname) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(email) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(category) LIKE ?", ['%' . strtolower($keyword) . '%'])
                    ->orWhereRaw("LOWER(problem_description) LIKE ?", ['%' . strtolower($keyword) . '%']);
            });
        }

        $results = $query->get();

        if ($results->count() === 0) {
            return response()->json('No data found.', 200);
        }

        return response()->json($results);
    }
}
