<?php

namespace App\Http\Controllers;

use App\Models\StudySubject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudySubjectController extends Controller
{
    public function store(Request $request)
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'description' => trim((string) $request->input('description')),
        ]);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('study_subjects', 'name')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'description' => ['nullable', 'string', 'max:240'],
        ], [
            'name.required' => 'Digite o nome da materia.',
            'name.unique' => 'Voce ja cadastrou essa materia.',
        ]);

        StudySubject::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'description' => isset($data['description']) ? trim($data['description']) ?: null : null,
        ]);

        return redirect()->route('dashboard')->with('status', 'Materia criada com sucesso.');
    }
}
