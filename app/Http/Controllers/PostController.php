<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Http\Requests\PostStoreRequest;
use App\Models\Comment;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $posts = Post::query()
            ->orderByDesc('id')
            ->get();

        return view('post.index')->with(compact('posts', 'user'));
    }

    public function create()
    {
        return view('post.create');
    }

    public function store(PostStoreRequest $request)
    {
        $files = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $media) {
                $name = time().rand(1,100000000).'.'.$media->extension();
                Storage::disk('public')->putFileAs('posts', $media, $name);
                $files[] = $name;
            }
        }

        $post = Post::create([
            'title' => $request->title,
            'description' => $request->description,
            'media' => implode(',', $files),
            'slug' => Str::slug($request->title),
            'user_id' => Auth::id(),
        ]);

        return response()->json($post->toArray());
    }

    public function show($slug)
    {
        $post = Post::query()
            ->with('user')
            ->where('slug', '=', $slug)
            ->first();

        if (!$post) {
            abort(404);
        }

        $media_path = [];
        $media = explode(',', $post->media);
        foreach ($media as $mdi) {
            if (!$mdi) {
                continue;
            }

            $media_path[] = asset('posts/'.$mdi);
        }

        return view('post.show', compact('post', 'media_path'));
    }

    public function comment(Post $post): JsonResponse
    {
        $post = $post->load(['comments', 'comments.commentator']);

        $comments = [];
        foreach ($post->comments as $comment) {
            $comments[] = $this->format($comment);
        }

        return response()->json($comments);
    }

    public function addComment(CommentRequest $request, Post $post)
    {
        $files = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $media) {
                $name = time().rand(1,100000000).'.'.$media->extension();
                Storage::disk('public')->putFileAs('posts', $media, $name);
                $files[] = $name;
            }
        }

        if ($request->has('comment_id') && $request->comment_id) {
            $comment = Comment::find($request->comment_id);
            if ($comment) {
                $comment->commentAsUserWithMedia(Auth::user(), $request->comment, implode(',', $files));
            }
        } else {
            $post->commentAsUserWithMedia(Auth::user(), $request->comment, implode(',', $files));
        }

        return response()->json("ok");
    }

    public function editComment()
    {

    }

    public function deleteComment()
    {

    }

    public function format($comment)
    {
        $nested = [];
        $comments = $comment->comments;
        if ($comments) {
            foreach ($comments as $cmt) {
                $nested[] = $this->format($cmt);
            }
        }

        $media_path = [];
        $media = explode(',', $comment->media);
        foreach ($media as $mdi) {
            if (!$mdi) {
                continue;
            }

            $media_path[] = asset('posts/'.$mdi);
        }

         return [
            'id' => $comment->id,
            'commentable_type' => $comment->commentable_type,
            'commentable_id' => $comment->commentable_id,
            'comment' => $comment->comment,
            'media' => $media_path,
            'is_approved' => $comment->is_approved,
            'created_at' => Carbon::parse($comment->created_at)->diffForHumans(),
            'commentator' => [
                'id' => data_get($comment, 'commentator.id', ''),
                'name' => data_get($comment, 'commentator.name', '')
            ],
            'nested' => $nested,
        ];
    }
}
