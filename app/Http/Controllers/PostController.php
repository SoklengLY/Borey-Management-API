<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;
use App\Models\Role;
use App\Models\postlike;
use App\Models\postcomment;
use App\Models\postshare;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isEmpty;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Check if the authenticated user is a company
        $user = auth()->user();
        // if ($user->role->name !== Role::USER) {
        //     return response("Your are not authorized to access this page", 405);
        // }

        $data = Post::with('user', 'comments.companies', 'comments.userInfo.user', 'userInfo', 'likes', 'shares')->latest()->get();

        if ($data->isEmpty()) {
            return response("No Data Available", 200);
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

        $validator = Validator::make($request->all(), [
            'content_type' => 'required',
            'heading' => 'required',
            'description' => 'required',
            'image' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user = auth()->user();
        // Check if the authenticated user is a company
        if ($user->role->name === Role::COMPANY) {
            Log::info('Company ID: ' . $user->company_id);
            $posts = Post::create([
                'company_id' => $user->company_id,
                'content_type' => $request->content_type,
                'heading' => $request->heading,
                'description' => $request->description,
                'image' => $request->image,
            ]);
            return response()->json($posts, 200);
        } else if ($user->role->name === Role::USER) {
            $posts = Post::create([
                'user_id' => $user->user_id, // Associate the user ID
                'content_type' => $request->content_type,
                'heading' => $request->heading,
                'description' => $request->description,
                'image' => $request->image,
            ]);
            return response()->json($posts, 200);
        } else {
            return response()->json(['error' => 'Admins are not allowed to create the records'], 403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $posts = Post::find($id);
        if (is_null($posts)) {
            return response()->json('Post not found', 404);
        }

        // Check if the authenticated user is the owner of the form
        $user = auth()->user();
        if ($user->user_id !== $posts->user_id && $user->role->name !== Role::COMPANY) {
            return response()->json('You are not authorized to view this Post', 403);
        }

        return response()->json($posts, 200);
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
        // Validating the request data
        $validator = Validator::make($request->all(), [
            'content_type' => 'required',
            'heading' => 'required',
            'description' => 'required',
            'image' => 'required',
        ]);

        // Handling validation errors
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user = auth()->user();
        // Retrieve the existing Security bill record
        $posts = Post::find($id);

        if (!$posts) {
            return response()->json('Post not found', 404);
        }

        // Check if the authenticated user is the owner of the user info
        if ($user->user_id !== $posts->user_id) {
            return response()->json('You are not authorized to update this post', 403);
        }

        // Updating the electric bill form with the request data
        $posts->content_type = $request->content_type;
        $posts->heading = $request->heading;
        $posts->description = $request->description;
        $posts->image = $request->image;

        // Saving the updated electric bill form
        $posts->save();

        return response()->json($posts, 200);
        // return response()->json(['Bill updated successfully.', new WaterbillsResource($waterbills)]);
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

        $posts = Post::find($id);

        if ($user->role->name === Role::COMPANY && $posts->company_id !== $user->company_id) {
            return response()->json('You are not authorized to delete this post', 403);
        } else if ($user->role->name === Role::USER && $posts->user_id !== $user->user_id) {
            return response()->json('You are not authorized to delete this post', 403);
        } else {
            $posts->delete();
            return response()->json('Post deleted successfully');
        }
    }

    /**
     * Search user info records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        // Retrieve the keyword from the request
        $keyword = $request->input('keyword');

        $user = auth()->user();

        $query = Post::query();

        if (!$user->role) {
            return response()->json('You are not authorized to perform this action', 403);
        }

        // Check if the authenticated user is a company
        if ($user->role->name === Role::COMPANY) {
            // Add your search criteria for company role here
            $query->where(function ($innerQuery) use ($keyword) {
                $innerQuery->where('username', 'like', "%$keyword%")
                    ->orWhere('content_type', 'like', "%$keyword%")
                    ->orWhere('heading', 'like', "%$keyword%")
                    ->orWhere('description', 'like', "%$keyword%")
                    ->orWhere('image', 'like', "%$keyword%");
            });
        } else {
            // Add your search criteria for other roles here
            $query->where('user_id', $user->id)->where(function ($innerQuery) use ($keyword) {
                $innerQuery->where('username', 'like', "%$keyword%")
                    ->orWhere('content_type', 'like', "%$keyword%")
                    ->orWhere('heading', 'like', "%$keyword%")
                    ->orWhere('description', 'like', "%$keyword%")
                    ->orWhere('image', 'like', "%$keyword%");
            });
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            return response()->json('No data found.', 404);
        }

        return response()->json($results);
    }

    public function storeLike(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        if ($user->role->name === Role::USER) {
            // Check if the user has already liked the post
            $existingLike = postlike::where('user_id', $user->user_id)->where('post_id', $request->post_id)->first();

            if ($existingLike) {
                return response()->json('You have already liked this post', 400);
            }

            // Create a new like record
            $like = postlike::create([
                'user_id' => $user->user_id,
                'post_id' => $request->post_id,
            ]);

            return response()->json($like, 200);
        } else if ($user->role->name === Role::COMPANY) {
            $existingLike = postlike::where('company_id', $user->company_id)->where('post_id', $request->post_id)->first();

            if ($existingLike) {
                return response()->json('You have already liked this post', 400);
            }

            // Create a new like record
            $like = postlike::create([
                'company_id' => $user->company_id,
                'post_id' => $request->post_id,
            ]);

            return response()->json($like, 200);
        }
    }

    public function destroyLike(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        // Find the like record associated with the user and post
        $like = postlike::where('user_id', $user->user_id)->where('post_id', $request->post_id)->first();

        if (!$like) {
            return response()->json('You have not liked this post', 400);
        }

        // Delete the like record
        $like->delete();

        return response()->json('Like removed successfully', 200);
    }




    public function storeComment(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        if ($user->role->name === Role::COMPANY) {
            $comment = postcomment::create([
                'company_id' => $user->company_id,
                'post_id' => $request->post_id,
                'content' => $request->content,
            ]);
            return response()->json($comment, 200);
        } else if ($user->role->name === Role::USER) {
            $comment = postcomment::create([
                'user_id' => $user->user_id,
                'post_id' => $request->post_id,
                'content' => $request->content,
            ]);
            return response()->json($comment, 200);
        } else {
            return response()->json("You are not allowed to comment", 409);
        }

        // Create a new comment record


    }

    public function deleteComment(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|exists:postcomments,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $comment = postcomment::find($request->comment_id);

        // Check if the comment exists and if the user is the owner of the comment or the owner of the post
        if ($comment && ($user->user_id === $comment->user_id || $user->user_id === $comment->post->user_id)) {
            $comment->delete();
            return response()->json('Comment deleted successfully', 200);
        }

        return response()->json('Unauthorized to delete the comment', 401);
    }

    public function storeShare(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        // Create a new share record
        $share = postshare::create([
            'user_id' => $user->user_id,
            'post_id' => $request->post_id,
        ]);

        return response()->json($share, 200);
    }
    public function deleteShare(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'share_id' => 'required|exists:postshares,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $share = postshare::find($request->share_id);

        // Check if the share exists and if the user is the owner of the share
        if ($share && ($user->user_id === $share->user_id)) {
            $share->delete();
            return response()->json('Share deleted successfully', 200);
        }

        return response()->json('Unauthorized to delete the share', 401);
    }
}
