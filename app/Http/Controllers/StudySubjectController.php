<?php

namespace App\Http\Controllers;

use App\Models\StudySubject;
use App\Support\StudySubjectCards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudySubjectController extends Controller
{
    public function index(Request $request, StudySubjectCards $subjectCards)
    {
        $today = now()->toDateString();
        $studySubjects = $request->user()->studySubjects()
            ->withSum([
                'studySessions as duration_seconds_total' => fn ($query) => $query->whereNotNull('ended_at'),
            ], 'duration_seconds')
            ->withSum([
                'studySessions as duration_seconds_today' => fn ($query) => $query
                    ->whereNotNull('ended_at')
                    ->whereDate('started_at', $today),
            ], 'duration_seconds')
            ->withSum([
                'studySessions as duration_seconds_week' => fn ($query) => $query
                    ->whereNotNull('ended_at')
                    ->whereBetween('started_at', [
                        now()->startOfWeek()->toDateTimeString(),
                        now()->endOfWeek()->toDateTimeString(),
                    ]),
            ], 'duration_seconds')
            ->withMax([
                'studySessions as last_studied_at' => fn ($query) => $query->whereNotNull('ended_at'),
            ], 'started_at')
            ->get();

        $orderedSubjects = $subjectCards->sortByRecentActivity($studySubjects);

        return view('study-subjects.index', [
            'studySubjects' => $orderedSubjects,
            'subjects' => $subjectCards->build($orderedSubjects),
        ]);
    }

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
                'max:50',
                Rule::unique('study_subjects', 'name')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'description' => ['nullable', 'string', 'max:240'],
            'goal_value' => ['nullable', 'numeric', 'min:0'],
            'goal_unit' => ['nullable', Rule::in(['minutes', 'hours'])],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Digite o nome da materia.',
            'name.max' => 'O nome da materia deve ter no maximo 50 caracteres.',
            'name.unique' => 'Voce ja cadastrou essa materia.',
            'photo.image' => 'Envie uma imagem valida para a materia.',
            'photo.mimes' => 'A foto precisa ser JPG, PNG ou WEBP.',
            'photo.max' => 'A foto da materia pode ter no maximo 2 MB.',
        ]);

        $goalMinutes = $this->normalizeGoalMinutes($data['goal_value'] ?? null, $data['goal_unit'] ?? null);
        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('study-subjects', 'public')
            : null;

        StudySubject::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'description' => isset($data['description']) ? trim($data['description']) ?: null : null,
            'goal_period' => $goalMinutes ? 'weekly' : null,
            'goal_minutes' => $goalMinutes,
            'photo_path' => $photoPath,
        ]);

        return $this->redirectAfterSave($request)->with('status', 'Materia criada com sucesso.');
    }

    public function update(Request $request, StudySubject $studySubject)
    {
        abort_unless($studySubject->user_id === $request->user()->id, 403);

        $request->merge([
            'name' => trim((string) $request->input('name')),
            'description' => trim((string) $request->input('description')),
        ]);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('study_subjects', 'name')
                    ->ignore($studySubject)
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'description' => ['nullable', 'string', 'max:240'],
            'goal_value' => ['nullable', 'numeric', 'min:0'],
            'goal_unit' => ['nullable', Rule::in(['minutes', 'hours'])],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Digite o nome da materia.',
            'name.max' => 'O nome da materia deve ter no maximo 50 caracteres.',
            'name.unique' => 'Voce ja cadastrou essa materia.',
            'photo.image' => 'Envie uma imagem valida para a materia.',
            'photo.mimes' => 'A foto precisa ser JPG, PNG ou WEBP.',
            'photo.max' => 'A foto da materia pode ter no maximo 2 MB.',
        ]);

        $goalMinutes = $this->normalizeGoalMinutes($data['goal_value'] ?? null, $data['goal_unit'] ?? null);

        $updates = [
            'name' => $data['name'],
            'description' => isset($data['description']) ? trim($data['description']) ?: null : null,
            'goal_period' => $goalMinutes ? 'weekly' : null,
            'goal_minutes' => $goalMinutes,
        ];

        if ($request->hasFile('photo')) {
            if ($studySubject->photo_path) {
                Storage::disk('public')->delete($studySubject->photo_path);
            }

            $updates['photo_path'] = $request->file('photo')->store('study-subjects', 'public');
        }

        $studySubject->forceFill($updates)->save();

        return $this->redirectAfterSave($request)->with('status', 'Materia atualizada com sucesso.');
    }

    public function destroy(Request $request, StudySubject $studySubject)
    {
        abort_unless($studySubject->user_id === $request->user()->id, 403);

        $hasRunningSession = $studySubject->studySessions()
            ->whereNull('ended_at')
            ->exists();

        if ($hasRunningSession) {
            return redirect()
                ->route('study-subjects.index')
                ->withErrors([
                    'subject' => 'Finalize a sessao em andamento antes de excluir essa materia.',
                ]);
        }

        if ($studySubject->photo_path) {
            Storage::disk('public')->delete($studySubject->photo_path);
        }

        $studySubject->delete();

        return redirect()->route('study-subjects.index')->with('status', 'Materia excluida com sucesso.');
    }

    private function normalizeGoalMinutes(mixed $value, ?string $unit): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numericValue = (float) $value;

        if ($numericValue <= 0) {
            return null;
        }

        return max(1, (int) round($unit === 'hours' ? $numericValue * 60 : $numericValue));
    }

    private function redirectAfterSave(Request $request)
    {
        if ($request->input('return_to') === 'subjects') {
            return redirect()->route('study-subjects.index');
        }

        return redirect()->route('dashboard');
    }
}
