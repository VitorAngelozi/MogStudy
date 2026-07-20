<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DailyLogController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'log_date' => ['nullable', 'date'],
            'title' => ['required', 'string', 'max:120'],
            'content' => ['required', 'string', 'max:20000'],
        ]);

        $logDate = isset($data['log_date'])
            ? Carbon::parse($data['log_date'])->toDateString()
            : now()->toDateString();

        $studyMinutes = (int) $request->user()->studySessions()
            ->whereDate('started_at', $logDate)
            ->sum('duration_seconds');

        DailyLog::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'log_date' => $logDate,
            ],
            [
                'title' => $data['title'],
                'content' => $data['content'],
                'study_minutes' => intdiv($studyMinutes, 60),
            ]
        );

        return redirect()->route('dashboard')->with('status', 'Registro diário salvo com sucesso.');
    }
}
