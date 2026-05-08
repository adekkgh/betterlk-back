<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\NewsFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    // GET /api/v1/news?page=1&per_page=10
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 10), 50);

        $news = News::with(['author:id,name', 'files'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $news->map(fn($n) => $this->format($n)),
            'meta' => [
                'current_page' => $news->currentPage(),
                'last_page'    => $news->lastPage(),
                'total'        => $news->total(),
                'has_more'     => $news->hasMorePages(),
            ],
        ]);
    }

    // GET /api/v1/news/{id}
    public function show(int $id): JsonResponse
    {
        $news = News::with(['author:id,name', 'files'])->findOrFail($id);
        return response()->json(['data' => $this->format($news)]);
    }

    // POST /api/v1/news  (multipart/form-data)
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()?->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'body'       => ['required', 'string'],
            'photos'     => ['sometimes', 'array', 'max:10'],
            'photos.*'   => ['file', 'mimes:jpg,jpeg,png,gif,webp', 'max:40960'], // 40 MB
        ]);

        $news = News::create([
            'created_by' => $request->user()->id,
            'title'      => $request->title,
            'body'       => $request->body,
        ]);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $i => $file) {
                $path = $file->store('news', 'public');
                NewsFile::create([
                    'news_id'    => $news->id,
                    'file_path'  => $path,
                    'file_name'  => $file->getClientOriginalName(),
                    'mime_type'  => $file->getMimeType(),
                    'file_size'  => $file->getSize(),
                    'sort_order' => $i,
                ]);
            }
        }

        return response()->json([
            'message' => 'Новость опубликована.',
            'data'    => $this->format($news->fresh(['author', 'files'])),
        ], 201);
    }

    // DELETE /api/v1/news/{id}
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()?->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $news = News::with('files')->findOrFail($id);

        foreach ($news->files as $file) {
            Storage::disk('public')->delete($file->file_path);
        }

        $news->delete();
        return response()->json(['message' => 'Новость удалена.']);
    }

    private function format(News $n): array
    {
        return [
            'id'         => $n->id,
            'title'      => $n->title,
            'body'       => $n->body,
            'author'     => $n->author ? ['id' => $n->author->id, 'name' => $n->author->name] : null,
            'photos'     => $n->files->map(fn($f) => [
                'id'        => $f->id,
                'url'       => Storage::disk('public')->url($f->file_path),
                'file_name' => $f->file_name,
                'file_size' => $f->file_size,
            ])->values(),
            'created_at' => $n->created_at->toIso8601String(),
        ];
    }
}
