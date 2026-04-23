<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeworkController extends Controller
{
    // Проверка роли
    private function canManage(Request $request): bool
    {
        return $request->user()->hasAnyRole(['admin', 'moderator', 'professor']);
    }

    // GET /api/v1/homeworks — список заданий
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Не авторизован.'], 401);
        }
        $user->load('role');

        $query = Homework::with(['group', 'creator', 'submissions.files', 'submissions.student'])
            ->orderBy('deadline', 'asc');

        // Студент видит только задания своей группы
        if ($user->hasRole('student')) {
            $groupId = $user->studentProfile?->group_id;
            if (!$groupId) {
                return response()->json(['data' => []]);
            }
            $query->where('group_id', $groupId);
        }

        // Препод видит только задания которые сам создал
        if ($user->hasRole('professor')) {
            $query->where('created_by', $user->id);
        }

        $homeworks = $query->get()->map(function ($hw) use ($user) {
            $submission = $hw->submissions
                ->where('student_id', $user->id)
                ->first();

            return [
                'id'                => $hw->id,
                'title'             => $hw->title,
                'description'       => $hw->description,
                'type'              => $hw->type,
                'max_score'         => $hw->max_score,
                'deadline'          => $hw->deadline,
                'deadline_extended' => $hw->deadline_extended,
                'extended_deadline' => $hw->extended_deadline,
                'is_expired'        => $hw->isExpired(),
                'group'             => $hw->group,
                'creator'           => [
                    'id'   => $hw->creator->id,
                    'name' => $hw->creator->name,
                ],
                'submission' => $user->hasRole('student')
                    ? ($submission ? [
                        'id'         => $submission->id,
                        'score'      => $submission->score,
                        'comment'    => $submission->comment,
                        'is_checked' => $submission->is_checked,
                        'checked_at' => $submission->checked_at,
                        'files'      => $submission->files,
                    ] : null)
                    : null,
                'submissions' => !$user->hasRole('student')
                    ? $hw->submissions->map(fn($sub) => [
                        'id'         => $sub->id,
                        'student'    => $sub->student ? ['id' => $sub->student->id, 'name' => $sub->student->name] : null,
                        'score'      => $sub->score,
                        'comment'    => $sub->comment,
                        'is_checked' => $sub->is_checked,
                        'checked_at' => $sub->checked_at,
                        'files'      => $sub->files,
                    ])
                    : null,
            ];
        });

        return response()->json(['data' => $homeworks]);
    }

    // GET /api/v1/homeworks/{id} — одно задание
    public function show(Request $request, int $id): JsonResponse
    {
        $homework = Homework::with(['group', 'creator', 'submissions.student', 'submissions.files'])
            ->findOrFail($id);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Не авторизован.'], 401);
        }
        $user->load('role');

        // Студент видит только своё задание
        if ($user->hasRole('student')) {
            $submission = $homework->submissions
                ->where('student_id', $user->id)
                ->first();

            return response()->json([
                'data' => array_merge($homework->toArray(), [
                    'submission' => $submission,
                    'is_expired' => $homework->isExpired(),
                ])
            ]);
        }

        // Препод и выше видят все ответы
        return response()->json([
            'data' => array_merge($homework->toArray(), [
                'submissions' => $homework->submissions,
                'is_expired'  => $homework->isExpired(),
            ])
        ]);
    }

    // POST /api/v1/homeworks — создать задание
    public function store(Request $request): JsonResponse
    {
        if (!$this->canManage($request)) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $request->validate([
            'group_id'    => ['required', 'exists:groups,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type'        => ['required', 'in:written,oral,file,link'],
            'max_score'   => ['required', 'integer', 'min:1', 'max:1000'],
            'deadline'    => ['required', 'date', 'after:now'],
        ]);

        $homework = Homework::create([
            'group_id'    => $request->group_id,
            'created_by'  => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description,
            'type'        => $request->type,
            'max_score'   => $request->max_score,
            'deadline'    => $request->deadline,
        ]);

        return response()->json([
            'message' => 'Задание создано.',
            'data'    => $homework->load(['group', 'creator']),
        ], 201);
    }

    // PUT /api/v1/homeworks/{id} — обновить задание
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->canManage($request)) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $homework = Homework::findOrFail($id);

        $request->validate([
            'title'             => ['sometimes', 'string', 'max:255'],
            'description'       => ['sometimes', 'nullable', 'string'],
            'type'              => ['sometimes', 'in:written,oral,file,link'],
            'max_score'         => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'deadline'          => ['sometimes', 'date'],
            'extended_deadline' => ['sometimes', 'nullable', 'date', 'after:deadline'],
        ]);

        // Если передали extended_deadline — значит продлеваем
        if ($request->has('extended_deadline') && $request->extended_deadline) {
            $homework->update([
                'deadline_extended' => true,
                'extended_deadline' => $request->extended_deadline,
            ]);
        }

        $homework->update($request->only([
            'title', 'description', 'type', 'max_score', 'deadline',
        ]));

        return response()->json([
            'message' => 'Задание обновлено.',
            'data'    => $homework->fresh()->load(['group', 'creator']),
        ]);
    }

    // DELETE /api/v1/homeworks/{id} — удалить задание
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$this->canManage($request)) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $homework = Homework::findOrFail($id);
        $homework->delete();

        return response()->json(['message' => 'Задание удалено.']);
    }

    // POST /api/v1/homeworks/{id}/submit — сдать задание (студент)
    public function submit(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Не авторизован.'], 401);
        }
        $user->load('role');

        if (!$user->hasRole('student')) {
            return response()->json(['message' => 'Только студенты могут сдавать задания.'], 403);
        }

        $homework = Homework::findOrFail($id);

        if ($homework->isExpired()) {
            return response()->json(['message' => 'Дедлайн истёк.'], 422);
        }

        // Ищем существующий ответ или создаём новый
        $submission = HomeworkSubmission::firstOrCreate([
            'homework_id' => $homework->id,
            'student_id'  => $user->id,
        ]);

        // Если задание уже проверено — нельзя изменить
        if ($submission->is_checked) {
            return response()->json(['message' => 'Задание уже проверено.'], 422);
        }

        // Загружаем файлы если есть
        if ($request->hasFile('files')) {
            $request->validate([
                'files'   => ['array', 'max:5'],
                'files.*' => ['file', 'max:20480'], // 20MB на файл
            ]);

            foreach ($request->file('files') as $file) {
                $path = $file->store("submissions/{$submission->id}", 'public');

                SubmissionFile::create([
                    'submission_id' => $submission->id,
                    'file_path'     => $path,
                    'file_type'     => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'file_size'     => $file->getSize(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Задание сдано.',
            'data'    => $submission->load('files'),
        ]);
    }

    // DELETE /api/v1/submissions/{submissionId}/files/{fileId} — удалить файл
    public function deleteFile(Request $request, int $submissionId, int $fileId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Не авторизован.'], 401);
        }
        $user->load('role');
        $submission = HomeworkSubmission::findOrFail($submissionId);

        // Только владелец и только если не проверено
        if ($submission->student_id !== $user->id) {
            return response()->json(['message' => 'Нет доступа.'], 403);
        }

        if ($submission->is_checked) {
            return response()->json(['message' => 'Задание уже проверено.'], 422);
        }

        $file = SubmissionFile::where('submission_id', $submissionId)
            ->findOrFail($fileId);

        Storage::disk('public')->delete($file->file_path);
        $file->delete();

        return response()->json(['message' => 'Файл удалён.']);
    }

    // POST /api/v1/submissions/{id}/check — проверить задание
    public function check(Request $request, int $id): JsonResponse
    {
        if (!$this->canManage($request)) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $submission = HomeworkSubmission::with('homework')->findOrFail($id);

        $request->validate([
            'score'   => ['required', 'integer', 'min:0', 'max:' . $submission->homework->max_score],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $submission->update([
            'score'      => $request->score,
            'comment'    => $request->comment,
            'is_checked' => true,
            'checked_at' => now(),
            'checked_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Задание проверено.',
            'data'    => $submission->fresh()->load(['student', 'files', 'checker']),
        ]);
    }
}
