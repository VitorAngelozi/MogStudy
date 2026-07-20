<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\User;
use App\Support\ActivityHeatmap;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(User $user)
    {
        $logs = $user->dailyLogs()
            ->latest('log_date')
            ->latest('created_at')
            ->limit(12)
            ->get();

        $sessions = $user->studySessions()
            ->latest('started_at')
            ->limit(8)
            ->get();

        $heatmap = app(ActivityHeatmap::class)->build($user->id);

        return view('profile', [
            'profileUser' => $user,
            'logs' => $logs,
            'sessions' => $sessions,
            'heatmap' => $heatmap,
            'streak' => $this->buildStreak($user->id),
        ]);
    }

    public function update(Request $request)
    {
        $request->merge([
            'profile_title' => trim((string) $request->input('profile_title')),
            'bio' => trim((string) $request->input('bio')),
        ]);

        $data = $request->validate([
            'profile_title' => ['nullable', 'string', 'max:50'],
            'bio' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'profile_title.max' => 'O titulo pode ter no maximo 50 caracteres.',
            'bio.max' => 'A bio pode ter no maximo 500 caracteres.',
            'photo.image' => 'Envie uma imagem valida para o perfil.',
            'photo.mimes' => 'A foto precisa ser JPG, PNG ou WEBP.',
            'photo.max' => 'A foto do perfil pode ter no maximo 2 MB.',
        ]);

        $user = $request->user();
        $updates = [
            'profile_title' => $data['profile_title'] ?: null,
            'bio' => $data['bio'] ?: null,
        ];

        if ($request->hasFile('photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $updates['profile_photo_path'] = $request->file('photo')->store('profile-photos', 'public');
        }

        $user->forceFill($updates)->save();

        return redirect()
            ->route('profile.show', $user)
            ->with('status', 'Perfil atualizado com sucesso.');
    }

    private function buildStreak(int $userId): int
    {
        $dates = DailyLog::query()
            ->where('user_id', $userId)
            ->pluck('log_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        $streak = 0;
        $cursor = now()->startOfDay();

        if (! $dates->contains($cursor->toDateString())) {
            $cursor = $cursor->subDay();
        }

        while ($dates->contains($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }
}
