<?php

namespace App\Http\Controllers;

use App\Models\CirclePost;
use Illuminate\Http\Request;

class CirclePostController extends Controller
{
    public function store(Request $request)
    {
        $request->merge([
            'title' => trim((string) $request->input('title')),
            'body' => trim((string) $request->input('body')),
        ]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:200'],
        ], [
            'title.max' => 'O titulo pode ter no maximo 80 caracteres.',
            'body.max' => 'O post pode ter no maximo 200 caracteres.',
        ]);

        $request->user()->circlePosts()->create($data);

        return redirect()->route('dashboard')->with('status', 'Post publicado no ciclo.');
    }

    public function reply(Request $request, CirclePost $post)
    {
        abort_unless($request->user()->isCircleMemberWith($post->user), 403);

        $request->merge([
            'body' => trim((string) $request->input('body')),
        ]);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:200'],
        ], [
            'body.max' => 'A resposta pode ter no maximo 200 caracteres.',
        ]);

        $post->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return redirect()->route('dashboard')->with('status', 'Resposta enviada.');
    }
}
