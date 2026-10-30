<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\LibraryCategory;
use App\Models\LibraryDownloadRequest;
use App\Models\LibraryReadingProgress;
use App\Models\LibraryResource;
use App\Services\Files\FilePreviewService;
use App\Services\Library\LibraryDownloadRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The digital library for the participant app: browse, read PDFs page by page,
 * save progress, bookmark and request downloads. Mirrors the website's rules:
 * participants see public and signed-in books, and reading progress is shared
 * with the website's "Continue reading".
 */
class LibraryController extends Controller
{
    private const VISIBLE_LEVELS = ['public', 'authenticated'];

    public function index(Request $request)
    {
        $query = LibraryResource::with('category')->where('is_active', true)->whereIn('access_level', self::VISIBLE_LEVELS);

        if ($search = trim((string) $request->get('search'))) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }
        if ($category = $request->integer('category')) {
            $query->where('library_category_id', $category);
        }

        $page = $query->latest()->paginate(20);
        $books = $this->present($request, collect($page->items()));

        return response()->json([
            'data' => $books,
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
            'categories' => LibraryCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Books in progress (most recent first) and bookmarks. */
    public function mine(Request $request)
    {
        $userId = $request->user()->id;
        $visible = fn ($q) => $q->where('is_active', true)->whereIn('access_level', self::VISIBLE_LEVELS);

        $reading = LibraryResource::with('category')->where($visible)
            ->join('library_reading_progress', 'library_reading_progress.library_resource_id', '=', 'library_resources.id')
            ->where('library_reading_progress.user_id', $userId)
            ->whereNull('library_reading_progress.completed_at')
            ->orderByDesc('library_reading_progress.last_read_at')
            ->select('library_resources.*')->take(12)->get();

        $bookmarks = LibraryResource::with('category')->where($visible)
            ->join('library_bookmarks', 'library_bookmarks.library_resource_id', '=', 'library_resources.id')
            ->where('library_bookmarks.user_id', $userId)
            ->orderByDesc('library_bookmarks.created_at')
            ->select('library_resources.*')->take(50)->get();

        return response()->json([
            'continue' => $this->present($request, $reading),
            'bookmarks' => $this->present($request, $bookmarks),
        ]);
    }

    public function show(Request $request, LibraryResource $resource)
    {
        $this->authorise($resource);
        $resource->load('category');

        $book = $this->present($request, collect([$resource]))[0];
        $latest = LibraryDownloadRequest::where('user_id', $request->user()->id)
            ->where('library_resource_id', $resource->id)->latest('id')->first();
        $book['download_request'] = $latest ? ['status' => $latest->status, 'expires_at' => $latest->expires_at?->toIso8601String()] : null;

        return response()->json(['data' => $book]);
    }

    public function cover(LibraryResource $resource)
    {
        $this->authorise($resource);
        abort_unless($resource->cover_image_path && Storage::disk('local')->exists($resource->cover_image_path), 404);

        return Storage::disk('local')->response($resource->cover_image_path);
    }

    /**
     * PDF bytes for the in-app reader, base64-encoded as plain text like the
     * website reader (some antivirus software blanks raw PDF responses).
     * Opening the book is recorded so it shows in "Continue reading".
     */
    public function file(Request $request, LibraryResource $resource)
    {
        $this->authorise($resource);
        abort_unless($resource->isPdf() && Storage::disk('local')->exists($resource->file_path), 404, 'This book can only be opened on the website.');
        abort_if(Storage::disk('local')->size($resource->file_path) > FilePreviewService::MAX_BYTES, 413, 'This book is too large to read in the app.');

        $progress = LibraryReadingProgress::firstOrNew(['user_id' => $request->user()->id, 'library_resource_id' => $resource->id]);
        $progress->first_opened_at ??= now();
        $progress->last_read_at = now();
        $progress->opened_count = (int) $progress->opened_count + 1;
        $progress->save();
        if ($progress->opened_count === 1) {
            $resource->increment('views_count');
        }

        return response(base64_encode(Storage::disk('local')->get($resource->file_path)), 200, [
            'Content-Type' => 'text/plain; charset=US-ASCII',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function progress(Request $request, LibraryResource $resource)
    {
        $this->authorise($resource);
        $data = $request->validate([
            'page' => ['required', 'integer', 'min:1', 'max:100000'],
            'total_pages' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);
        $page = min($data['page'], $data['total_pages']);

        $progress = LibraryReadingProgress::firstOrNew(['user_id' => $request->user()->id, 'library_resource_id' => $resource->id]);
        $progress->first_opened_at ??= now();
        $progress->current_page = $page;
        $progress->total_pages = $data['total_pages'];
        $progress->percent = round($page / $data['total_pages'] * 100, 2);
        $progress->last_read_at = now();
        if ($page >= $data['total_pages'] && ! $progress->completed_at) {
            $progress->completed_at = now();
        }
        $progress->save();

        return response()->json(['progress' => $this->presentProgress($progress)]);
    }

    public function bookmark(Request $request, LibraryResource $resource)
    {
        $this->authorise($resource);
        $userId = $request->user()->id;

        $existing = DB::table('library_bookmarks')->where('user_id', $userId)->where('library_resource_id', $resource->id);
        if ($existing->exists()) {
            $existing->delete();

            return response()->json(['bookmarked' => false]);
        }

        DB::table('library_bookmarks')->insert(['user_id' => $userId, 'library_resource_id' => $resource->id, 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['bookmarked' => true]);
    }

    public function requestDownload(Request $request, LibraryResource $resource, LibraryDownloadRequestService $requests)
    {
        $this->authorise($resource);
        abort_unless($resource->file_path, 404);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        $result = $requests->request($request->user(), $resource, $data['reason'] ?? null);

        return response()->json([
            'status' => $result,
            'message' => match ($result) {
                LibraryDownloadRequestService::ALREADY_ALLOWED => 'You can already save a copy of this book on the website.',
                LibraryDownloadRequestService::ALREADY_PENDING => 'Your request for this book is already waiting for the library team.',
                default => 'Request sent. You will get a notification when the library team replies. You can keep reading in the app meanwhile.',
            },
        ]);
    }

    private function authorise(LibraryResource $resource): void
    {
        abort_unless($resource->is_active && in_array($resource->access_level, self::VISIBLE_LEVELS, true), 404);
    }

    /** @param Collection<int, LibraryResource> $resources */
    private function present(Request $request, Collection $resources): array
    {
        $user = $request->user();
        $ids = $resources->pluck('id');
        $bookmarked = DB::table('library_bookmarks')->where('user_id', $user->id)->whereIn('library_resource_id', $ids)->pluck('library_resource_id')->flip();
        $progress = LibraryReadingProgress::where('user_id', $user->id)->whereIn('library_resource_id', $ids)->get()->keyBy('library_resource_id');

        return $resources->map(fn (LibraryResource $r) => [
            'id' => $r->id,
            'title' => $r->title,
            'author' => $r->author,
            'description' => $r->description,
            'language' => $r->language,
            'category' => $r->category ? ['id' => $r->category->id, 'name' => $r->category->name] : null,
            'cover_path' => $r->cover_image_path ? "/library/{$r->id}/cover" : null,
            'readable_in_app' => $r->isPdf(),
            'external_url' => $r->external_url,
            'web_url' => route('library.show', $r),
            'download_policy' => $r->download_policy,
            'can_download' => $r->canBeDownloadedBy($user),
            'bookmarked' => $bookmarked->has($r->id),
            'progress' => ($p = $progress->get($r->id)) ? $this->presentProgress($p) : null,
        ])->values()->all();
    }

    private function presentProgress(LibraryReadingProgress $p): array
    {
        return [
            'page' => $p->current_page ? (int) $p->current_page : null,
            'total_pages' => $p->total_pages ? (int) $p->total_pages : null,
            'percent' => (float) $p->percent,
            'completed' => $p->completed_at !== null,
            'last_read_at' => $p->last_read_at?->toIso8601String(),
        ];
    }
}
