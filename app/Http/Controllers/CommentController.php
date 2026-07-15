<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index()
    {

    }

    public function create()
    {

    }

    public function store(Request $request)
    {
        $comment = new Comment();

        $comment->active = $request['active'];
        $comment->listing_id = $request['listing_id'];
        $comment->user_id = auth()->id();
        $comment->rating = $request['rating'];
        $comment->content = $request['content'];

        $comment->save();

        return redirect()->back();
    }

    public function show(Comment $comment)
    {

    }

    public function edit(Comment $comment)
    {

    }

    public function update(Request $request, Comment $comment)
    {

    }

    public function destroy(Comment $comment)
    {

    }
}
