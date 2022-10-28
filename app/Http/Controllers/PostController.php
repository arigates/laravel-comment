<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Http\Requests\PostStoreRequest;
use App\Http\Requests\PostUpdateRequest;
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

    public function edit(Post $post): JsonResponse
    {
        if (Auth::user()->role != 'admin') {
            abort(403);
        }

        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'description' => $post->description,
            'media' => $post->media ? explode(',', $post->media) : "",
        ];

        return response()->json($data);
    }

    public function update(Post $post, PostUpdateRequest $request)
    {
        $files = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $media) {
                $name = time().rand(1,100000000).'.'.$media->extension();
                Storage::disk('public')->putFileAs('posts', $media, $name);
                $files[] = $name;
            }
        }

        if ($files) {
            $media = explode(',', $post->media);
            if ($media) {
                $media = array_merge($media, $files);
            } else {
                $media = $files;
            }

            $post->media = implode(',', $media);
        }

        $post->title = $request->title;
        $post->description = $request->description;
        $post->update();

        return response()->json("ok");
    }

    public function delete(Post $post): JsonResponse
    {
        if (Auth::user()->role != 'admin') {
            abort(403);
        }

        $post->delete();

        return response()->json("ok");
    }

    public function deleteAttachment(Post $post, $attachment)
    {
        if (Auth::user()->role != 'admin') {
            abort(403);
        }

        $media = explode(',', $post->media);
        if (($key = array_search($attachment, $media)) !== false) {
            unset($media[$key]);
        }

        if (Storage::disk('public')->exists('posts/'.$attachment)) {
            Storage::disk('public')->delete('posts/'.$attachment);
        }

        $post->media = $media ? implode(',', $media) : null;
        $post->update();

        return response()->json("ok");
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

    public function deleteComment(Comment $comment): JsonResponse
    {
        if ($comment->user_id != Auth::id()) {
            abort(403);
        }

        $comment->delete();

        return response()->json("ok");
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
            'can_delete' => Auth::id() == $comment->user_id,
            'created_at' => Carbon::parse($comment->created_at)->diffForHumans(),
            'commentator' => [
                'id' => data_get($comment, 'commentator.id', ''),
                'name' => data_get($comment, 'commentator.name', '')
            ],
            'nested' => $nested,
        ];
    }
}
