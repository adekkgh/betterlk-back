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

        $query = Homework::with(['group', 'creator', 'subject', 'submissions.files', 'submissions.student'])
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
                'subject'           => $hw->subject ? ['id' => $hw->subject->id, 'name' => $hw->subject->name] : null,
                'creator'           => [
                    'id'   => $hw->creator->id,
                    'name' => $hw->creator->name,
                ],
                'submission' => $user->hasRole('student')
                    ? ($submission ? [
                        'id'         => $submission->id,
                        'score'      => $submission->score,
                        'comment'    => $submission->comment,
                        'student_comment' => $submission->student_comment,
                        'links'           => $submission->links,
                        'is_checked' => $submission->is_checked,
                        'checked_at' => $submission->checked_at,
                        'files' => $submission->files->map(fn($file) => [
                            'id'            => $file->id,
                            'original_name' => $file->original_name,
                            'file_type'     => $file->file_type,
                            'file_size'     => $file->file_size,
                            'url'           => asset('storage/' . $file->file_path),
                        ]),
                    ] : null)
                    : null,
                'submissions' => !$user->hasRole('student')
                    ? $hw->submissions->map(fn($sub) => [
                        'id'             => $sub->id,
                        'student'        => $sub->student ? ['id' => $sub->student->id, 'name' => $sub->student->name] : null,
                        'score'          => $sub->score,
                        'comment'        => $sub->comment,
                        'student_comment' => $sub->student_comment,
                        'links'           => $sub->links,
                        'is_checked'     => $sub->is_checked,
                        'checked_at'     => $sub->checked_at,
                        'files' => $sub->files->map(fn($file) => [
                            'id'            => $file->id,
                            'original_name' => $file->original_name,
                            'file_type'     => $file->file_type,
                            'file_size'     => $file->file_size,
                            'url'           => asset('storage/' . $file->file_path),
                        ]),
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
            'subject_id'  => ['nullable', 'exists:subjects,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_score'   => ['required', 'integer', 'min:1', 'max:1000'],
            'deadline'    => ['required', 'date', 'after:now'],
        ]);

        $homework = Homework::create([
            'group_id'    => $request->group_id,
            'subject_id'  => $request->subject_id,
            'created_by'  => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description,
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
            'title', 'description', 'max_score', 'deadline', 'subject_id',
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
        if (!$user) return response()->json(['message' => 'Не авторизован.'], 401);
        $user->load('role');

        if (!$user->hasRole('student')) {
            return response()->json(['message' => 'Только студенты могут сдавать задания.'], 403);
        }

        $homework = Homework::findOrFail($id);

        if ($homework->isExpired()) {
            return response()->json(['message' => 'Дедлайн истёк.'], 422);
        }

        $request->validate([
            'student_comment' => ['nullable', 'string', 'max:2000'],
            'links'           => ['nullable', 'array', 'max:10'],
            'files'           => ['nullable', 'array', 'max:5'],
            'files.*'         => [
                'file',
                'max:20480', // 20MB
                'mimes:' . implode(',', [
                    // Документы
                    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf',
                    // Изображения
                    'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp',
                    // Архивы
                    'zip', 'rar', '7z', 'tar', 'gz',
                    // Код
                    'py', 'js', 'ts', 'jsx', 'tsx', 'html', 'css', 'scss',
                    'php', 'java', 'cpp', 'c', 'h', 'cs', 'go', 'rs',
                    'sql', 'json', 'xml', 'yaml', 'yml', 'md', 'csv',
                    // Jupyter
                    'ipynb',
                ]),
            ],
        ]);

        // Хотя бы одно поле должно быть заполнено
        if (!$request->student_comment && !$request->links && !$request->hasFile('files')) {
            return response()->json([
                'message' => 'Добавьте комментарий, ссылку или прикрепите файл.',
            ], 422);
        }

        $submission = HomeworkSubmission::firstOrCreate([
            'homework_id' => $homework->id,
            'student_id'  => $user->id,
        ]);

        if ($submission->is_checked) {
            return response()->json(['message' => 'Задание уже проверено.'], 422);
        }

        // Обновляем текстовые поля если переданы
        $updateData = [];
        if ($request->has('student_comment')) {
            $updateData['student_comment'] = $request->student_comment;
        }
        if ($request->has('links')) {
            // Фильтруем пустые ссылки
            $updateData['links'] = array_values(array_filter($request->links ?? []));
        }
        if (!empty($updateData)) {
            $submission->update($updateData);
        }

        // Загружаем файлы
        if ($request->hasFile('files')) {
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
            'data'    => $submission->fresh()->load('files'),
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

    // POST /api/v1/submissions/{id}/recheck — снять проверку
    public function recheck(Request $request, int $id): JsonResponse
    {
        if (!$this->canManage($request)) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $submission = HomeworkSubmission::findOrFail($id);

        $submission->update([
            'is_checked' => false,
            'score'      => null,
            'comment'    => null,
            'checked_at' => null,
            'checked_by' => null,
        ]);

        return response()->json([
            'message' => 'Статус проверки сброшен.',
            'data'    => $submission->fresh(),
        ]);
    }
}
