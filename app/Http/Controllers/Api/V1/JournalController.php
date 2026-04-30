<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalRating;
use Illuminate\Http\JsonResponse;

class JournalController extends Controller
{
    private function canManage(Request $request): bool
    {
        return $request->user()?->hasAnyRole(['admin', 'moderator', 'professor']);
    }

    // GET /api/v1/journals — список журналов
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Не авторизован.'], 401);
        $user->load('role');

        $query = Journal::with(['subject', 'group', 'professor']);

        if ($user->hasRole('student')) {
            // Студент видит журналы своей группы
            $groupId = $user->studentProfile?->group_id;
            if (!$groupId) return response()->json(['data' => []]);
            $query->where('group_id', $groupId);
        } elseif ($user->hasRole('professor')) {
            // Препод видит только свои журналы
            $query->where('professor_id', $user->id);
        }
        // admin/moderator видят всё

        $journals = $query->orderBy('year', 'desc')
            ->orderBy('semester', 'desc')
            ->get()
            ->map(fn($j) => [
                'id'        => $j->id,
                'subject'   => $j->subject,
                'group'     => ['id' => $j->group->id, 'name' => $j->group->name],
                'professor' => ['id' => $j->professor->id, 'name' => $j->professor->name],
                'semester'  => $j->semester,
                'year'      => $j->year,
            ]);

        return response()->json(['data' => $journals]);
    }

    // GET /api/v1/journals/{id} — данные журнала (записи + рейтинги)
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Не авторизован.'], 401);
        $user->load('role');

        $journal = Journal::with([
            'subject', 'group.students.user', 'professor',
            'entries', 'ratings'
        ])->findOrFail($id);

        // Проверка доступа
        if ($user->hasRole('student')) {
            $groupId = $user->studentProfile?->group_id;
            if ($journal->group_id !== $groupId) {
                return response()->json(['message' => 'Нет доступа.'], 403);
            }
        } elseif ($user->hasRole('professor')) {
            if ($journal->professor_id !== $user->id) {
                return response()->json(['message' => 'Нет доступа.'], 403);
            }
        }

        // Список студентов группы
        $students = $journal->group->students->map(fn($sp) => [
            'id'   => $sp->user->id,
            'name' => $sp->user->name,
        ])->sortBy('name')->values();

        // Записи сгруппированные по student_id и дате
        $entriesMap = [];
        foreach ($journal->entries as $entry) {
            $entriesMap[$entry->student_id][$entry->date->format('Y-m-d')] = [
                'id'        => $entry->id,
                'is_absent' => $entry->is_absent,
                'score'     => $entry->score,
            ];
        }

        // Рейтинги по student_id
        $ratingsMap = [];
        foreach ($journal->ratings as $rating) {
            $ratingsMap[$rating->student_id] = [
                'id'           => $rating->id,
                'rating_score' => $rating->rating_score,
                'exam_score'   => $rating->exam_score,
            ];
        }

        return response()->json([
            'data' => [
                'id'        => $journal->id,
                'subject'   => $journal->subject,
                'group'     => ['id' => $journal->group->id, 'name' => $journal->group->name],
                'professor' => ['id' => $journal->professor->id, 'name' => $journal->professor->name],
                'semester'  => $journal->semester,
                'year'      => $journal->year,
                'students'  => $students,
                'entries'   => $entriesMap,  // { student_id: { 'YYYY-MM-DD': { is_absent, score } } }
                'ratings'   => $ratingsMap,  // { student_id: { rating_score, exam_score } }
            ]
        ]);
    }

    // POST /api/v1/journals — создать журнал
    public function store(Request $request): JsonResponse
    {
        if (!$this->canManage($request)) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $request->validate([
            'subject_id'   => ['required', 'exists:subjects,id'],
            'group_id'     => ['required', 'exists:groups,id'],
            'professor_id' => ['required', 'exists:users,id'],
            'semester'     => ['required', 'in:1,2'],
            'year'         => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $journal = Journal::create($request->only([
            'subject_id', 'group_id', 'professor_id', 'semester', 'year'
        ]));

        return response()->json([
            'message' => 'Журнал создан.',
            'data'    => $journal->load(['subject', 'group', 'professor']),
        ], 201);
    }

    // DELETE /api/v1/journals/{id}
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()?->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        Journal::findOrFail($id)->delete();
        return response()->json(['message' => 'Журнал удалён.']);
    }

    // POST /api/v1/journals/{id}/entry — выставить/обновить запись в ячейке
    public function upsertEntry(Request $request, int $id): JsonResponse
    {
        if (!$this->canManage($request)) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $journal = Journal::findOrFail($id);

        $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'date'       => ['required', 'date'],
            'is_absent'  => ['sometimes', 'boolean'],
            'score'      => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $entry = JournalEntry::updateOrCreate(
            [
                'journal_id' => $journal->id,
                'student_id' => $request->student_id,
                'date'       => $request->date,
            ],
            array_filter([
                'is_absent' => $request->has('is_absent') ? $request->is_absent : null,
                'score'     => $request->has('score') ? $request->score : null,
            ], fn($v) => $v !== null)
        );

        // Если ячейка пустая (is_absent=false и score=null) — удаляем запись
        $entry->refresh();
        if (!$entry->is_absent && $entry->score === null) {
            $entry->delete();
            return response()->json(['message' => 'Запись удалена.', 'data' => null]);
        }

        return response()->json(['message' => 'Запись сохранена.', 'data' => $entry]);
    }

    // POST /api/v1/journals/{id}/rating — выставить рейтинг/экзамен
    public function upsertRating(Request $request, int $id): JsonResponse
    {
        if (!$this->canManage($request)) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $journal = Journal::findOrFail($id);

        $request->validate([
            'student_id'   => ['required', 'exists:users,id'],
            'rating_score' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:30'],
            'exam_score'   => ['sometimes', 'nullable', 'integer', 'min:0', 'max:30'],
        ]);

        $rating = JournalRating::updateOrCreate(
            ['journal_id' => $journal->id, 'student_id' => $request->student_id],
            $request->only(['rating_score', 'exam_score'])
        );

        return response()->json(['message' => 'Оценка сохранена.', 'data' => $rating]);
    }
}
