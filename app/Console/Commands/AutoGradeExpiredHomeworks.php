<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Journal;
use App\Models\JournalEntry;
use Carbon\Carbon;

class AutoGradeExpiredHomeworks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'homeworks:auto-grade-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Выставить 0 в журнал студентам не сдавшим задание до дедлайна';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Находим просроченные задания с предметом
        $homeworks = Homework::with(['group.students.user.studentProfile'])
            ->whereNotNull('subject_id')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('deadline_extended', false)
                        ->where('deadline', '<', now());
                })->orWhere(function ($q2) {
                    $q2->where('deadline_extended', true)
                        ->whereNotNull('extended_deadline')
                        ->where('extended_deadline', '<', now());
                });
            })
            ->get();

        foreach ($homeworks as $homework) {
            $deadline = $homework->deadline_extended && $homework->extended_deadline
                ? Carbon::parse($homework->extended_deadline)
                : Carbon::parse($homework->deadline);

            $month       = (int) $deadline->format('n');
            $semester    = ($month >= 9 || $month === 1) ? 1 : 2;
            $journalYear = $semester === 1 && $month === 1 ? $deadline->year - 1 : $deadline->year;

            foreach ($homework->group->students as $studentProfile) {
                $studentId = $studentProfile->user_id;
                $groupId   = $studentProfile->group_id;

                // Пропускаем если студент сдал задание
                $submitted = HomeworkSubmission::where('homework_id', $homework->id)
                    ->where('student_id', $studentId)
                    ->exists();

                if ($submitted) continue;

                // Ищем журнал
                $journal = Journal::where('subject_id', $homework->subject_id)
                    ->where('group_id',  $groupId)
                    ->where('semester',  $semester)
                    ->where('year',      $journalYear)
                    ->first();

                if (!$journal) continue;

                // Выставляем 0 только если записи ещё нет
                JournalEntry::firstOrCreate(
                    [
                        'journal_id' => $journal->id,
                        'student_id' => $studentId,
                        'date'       => $deadline->format('Y-m-d'),
                    ],
                    [
                        'score'     => 0,
                        'is_absent' => false,
                    ]
                );

                $this->line("Выставлен 0: студент {$studentId}, задание {$homework->id}");
            }
        }

        $this->info('Готово.');
    }
}
