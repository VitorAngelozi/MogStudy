<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->foreignId('study_subject_id')
                ->nullable()
                ->after('user_id')
                ->constrained('study_subjects')
                ->nullOnDelete();
        });

        $now = now();

        DB::table('study_sessions')
            ->select('user_id', 'subject')
            ->whereNull('study_subject_id')
            ->whereNotNull('subject')
            ->where('subject', '<>', '')
            ->distinct()
            ->orderBy('user_id')
            ->orderBy('subject')
            ->get()
            ->each(function ($sessionSubject) use ($now): void {
                $subjectId = DB::table('study_subjects')
                    ->where('user_id', $sessionSubject->user_id)
                    ->where('name', $sessionSubject->subject)
                    ->value('id');

                if (! $subjectId) {
                    $subjectId = DB::table('study_subjects')->insertGetId([
                        'user_id' => $sessionSubject->user_id,
                        'name' => $sessionSubject->subject,
                        'description' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('study_sessions')
                    ->where('user_id', $sessionSubject->user_id)
                    ->where('subject', $sessionSubject->subject)
                    ->update([
                        'study_subject_id' => $subjectId,
                        'updated_at' => $now,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('study_subject_id');
        });
    }
};
