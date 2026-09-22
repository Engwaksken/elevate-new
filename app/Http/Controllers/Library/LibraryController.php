<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryCategory;
use App\Models\LibraryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $query = LibraryResource::with('category')->where('is_active',true);

        if (!auth()->check()) {
            $query->where('access_level','public');
        } elseif (!auth()->user()->isStaff()) {
            $query->whereIn('access_level',['public','authenticated']);
        }

        if ($search = trim((string)$request->get('search'))) {
            $query->where(fn($q)=>$q
                ->where('title','like',"%{$search}%")
                ->orWhere('author','like',"%{$search}%")
                ->orWhere('description','like',"%{$search}%"));
        }

        if ($category = $request->get('category')) {
            $query->where('library_category_id',$category);
        }

        return view('library.index', [
            'resources'=>$query->latest()->paginate(16)->withQueryString(),
            'categories'=>LibraryCategory::where('is_active',true)->orderBy('name')->get(),
        ]);
    }

    public function show(LibraryResource $resource)
    {
        $this->authoriseAccess($resource);
        $resource->increment('views_count');

        return view('library.show',compact('resource'));
    }

    public function download(LibraryResource $resource)
    {
        $this->authoriseAccess($resource);
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path),404);

        DB::table('library_downloads')->insert([
            'user_id'=>auth()->id(),
            'library_resource_id'=>$resource->id,
            'downloaded_at'=>now(),
            'ip_address'=>request()->ip(),
        ]);

        $resource->increment('downloads_count');

        return Storage::disk('local')->download($resource->file_path);
    }

    public function bookmark(LibraryResource $resource)
    {
        abort_unless(auth()->check(),401);
        DB::table('library_bookmarks')->updateOrInsert([
            'user_id'=>auth()->id(),
            'library_resource_id'=>$resource->id,
        ],[
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return back()->with('success','Resource bookmarked.');
    }

    private function authoriseAccess(LibraryResource $resource): void
    {
        abort_unless($resource->is_active,404);

        if ($resource->access_level === 'public') return;
        abort_unless(auth()->check(),401);
        if ($resource->access_level === 'staff') abort_unless(auth()->user()->isStaff(),403);
    }
}
