<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryCategory;
use App\Models\LibraryDownloadRequest;
use App\Models\LibraryReadingProgress;
use App\Models\LibraryResource;
use App\Services\Files\FilePreviewService;
use App\Services\Library\LibraryDownloadRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $query=LibraryResource::with('category')->where('is_active',true);

        $this->applyAccessScope($query);

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('title','like',"%{$search}%")
                    ->orWhere('author','like',"%{$search}%")
                    ->orWhere('description','like',"%{$search}%");
            });
        }

        if($category=$request->get('category')){
            $query->where('library_category_id',$category);
        }

        if($language=$request->get('language')){
            $query->where('language',$language);
        }

        return view('library.index',[
            'resources'=>$query->latest()->paginate(16)->withQueryString(),
            'categories'=>LibraryCategory::where('is_active',true)->orderBy('name')->get(),
            'languages'=>LibraryResource::query()
                ->where('is_active',true)
                ->whereNotNull('language')
                ->select('language')
                ->distinct()
                ->orderBy('language')
                ->pluck('language'),
            'bookmarkedIds'=>auth()->check() ? DB::table('library_bookmarks')->where('user_id',auth()->id())->pluck('library_resource_id')->all() : [],
            'progressByResource'=>auth()->check() ? LibraryReadingProgress::where('user_id',auth()->id())->get()->keyBy('library_resource_id') : collect(),
        ]);
    }

    public function show(LibraryResource $resource)
    {
        $this->authoriseAccess($resource);
        $resource->load('category');
        $resource->increment('views_count');

        $user=auth()->user();

        return view('library.show',[
            'resource'=>$resource,
            'bookmarked'=>$user && DB::table('library_bookmarks')->where('user_id',$user->id)->where('library_resource_id',$resource->id)->exists(),
            'progress'=>$user ? LibraryReadingProgress::where('user_id',$user->id)->where('library_resource_id',$resource->id)->first() : null,
            'canDownload'=>$resource->canBeDownloadedBy($user),
            'latestRequest'=>$user ? LibraryDownloadRequest::where('user_id',$user->id)->where('library_resource_id',$resource->id)->latest('id')->first() : null,
        ]);
    }

    public function cover(LibraryResource $resource)
    {
        $this->authoriseAccess($resource);

        abort_unless(
            $resource->cover_image_path
            && Storage::disk('local')->exists($resource->cover_image_path),
            404
        );

        return Storage::disk('local')->response($resource->cover_image_path);
    }

    public function download(LibraryResource $resource)
    {
        $this->authoriseAccess($resource);

        abort_unless(
            $resource->file_path
            && Storage::disk('local')->exists($resource->file_path),
            404
        );

        $canDownload=$resource->canBeDownloadedBy(auth()->user());

        // Reading (preview) is always allowed; the download button inside it only appears when saving a copy is allowed.
        if(app(FilePreviewService::class)->wantsPreview(request())){
            return app(FilePreviewService::class)->respond(request(),'local',$resource->file_path,basename($resource->file_path),$canDownload);
        }

        if(!$canDownload){
            return redirect()->route('library.show',$resource)
                ->with('warning','This book can be read online. To save a copy, send a download request and the library team will review it.');
        }

        if(auth()->check()){
            DB::table('library_downloads')->insert([
                'user_id'=>auth()->id(),
                'library_resource_id'=>$resource->id,
                'downloaded_at'=>now(),
                'ip_address'=>request()->ip(),
            ]);
        }

        $resource->increment('downloads_count');

        return Storage::disk('local')->download($resource->file_path);
    }

    /** Add or remove a bookmark. */
    public function bookmark(Request $request, LibraryResource $resource)
    {
        $this->authoriseAccess($resource);

        $existing=DB::table('library_bookmarks')->where('user_id',auth()->id())->where('library_resource_id',$resource->id);
        if($existing->exists()){
            $existing->delete();
            $bookmarked=false;
        }else{
            DB::table('library_bookmarks')->insert(['user_id'=>auth()->id(),'library_resource_id'=>$resource->id,'created_at'=>now(),'updated_at'=>now()]);
            $bookmarked=true;
        }

        if($request->expectsJson()){
            return response()->json(['bookmarked'=>$bookmarked]);
        }

        return back()->with('success',$bookmarked ? 'Added to your bookmarks.' : 'Removed from your bookmarks.');
    }

    /** Full-screen reader. PDFs open page by page at the saved page; other files use the view-only preview. */
    public function read(LibraryResource $resource)
    {
        $this->authoriseAccess($resource);
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path),404);

        $progress=LibraryReadingProgress::firstOrNew(['user_id'=>auth()->id(),'library_resource_id'=>$resource->id]);
        $progress->first_opened_at??=now();
        $progress->last_read_at=now();
        $progress->opened_count=(int)$progress->opened_count+1;
        $progress->save();

        if($progress->opened_count===1){
            $resource->increment('views_count');
        }

        return view('library.read',[
            'resource'=>$resource,
            'progress'=>$progress,
            'canDownload'=>$resource->canBeDownloadedBy(auth()->user()),
            'bookmarked'=>DB::table('library_bookmarks')->where('user_id',auth()->id())->where('library_resource_id',$resource->id)->exists(),
        ]);
    }

    /**
     * PDF bytes for the built-in reader, base64-encoded as plain text. Some antivirus/web-protection
     * software intercepts application/pdf responses in the browser (the page receives an empty 204);
     * text passes through untouched and the reader decodes it.
     */
    public function readFile(LibraryResource $resource)
    {
        $this->authoriseAccess($resource);
        abort_unless($resource->isPdf() && Storage::disk('local')->exists($resource->file_path),404);
        abort_if(Storage::disk('local')->size($resource->file_path)>FilePreviewService::MAX_BYTES,413,'This book is too large for the online reader.');

        return response(base64_encode(Storage::disk('local')->get($resource->file_path)),200,[
            'Content-Type'=>'text/plain; charset=US-ASCII',
            'Cache-Control'=>'private, no-store, max-age=0',
            'X-Content-Type-Options'=>'nosniff',
        ]);
    }

    /** Saved by the reader as pages are turned. */
    public function progress(Request $request, LibraryResource $resource)
    {
        $this->authoriseAccess($resource);
        $data=$request->validate([
            'page'=>['required','integer','min:1','max:100000'],
            'total_pages'=>['required','integer','min:1','max:100000'],
        ]);
        $page=min($data['page'],$data['total_pages']);

        $progress=LibraryReadingProgress::firstOrNew(['user_id'=>auth()->id(),'library_resource_id'=>$resource->id]);
        $progress->first_opened_at??=now();
        $progress->current_page=$page;
        $progress->total_pages=$data['total_pages'];
        $progress->percent=round($page/$data['total_pages']*100,2);
        $progress->last_read_at=now();
        if($page>=$data['total_pages'] && !$progress->completed_at){
            $progress->completed_at=now();
        }
        $progress->save();

        return response()->json([
            'page'=>$progress->current_page,
            'total_pages'=>$progress->total_pages,
            'percent'=>(float)$progress->percent,
            'completed'=>$progress->completed_at!==null,
        ]);
    }

    public function requestDownload(Request $request, LibraryResource $resource)
    {
        $this->authoriseAccess($resource);
        abort_unless($resource->file_path,404);

        $data=$request->validate(['reason'=>['nullable','string','max:1000']]);
        $result=app(LibraryDownloadRequestService::class)->request($request->user(),$resource,$data['reason']??null);

        if($result===LibraryDownloadRequestService::ALREADY_ALLOWED){
            return back()->with('success','You can already download this book.');
        }
        if($result===LibraryDownloadRequestService::ALREADY_PENDING){
            return back()->with('warning','Your request for this book is already waiting for the library team.');
        }

        return back()->with('success','Request sent. You will get a notification when the library team replies. You can keep reading online meanwhile.');
    }

    /** The participant's own reading: continue reading, bookmarks, history and download requests. */
    public function mine(Request $request)
    {
        $userId=auth()->id();
        $visible=fn($q)=>$q->where('is_active',true)->whereIn('access_level',auth()->user()->isStaff() ? ['public','authenticated','staff'] : ['public','authenticated']);

        $reading=LibraryReadingProgress::with('resource.category')->where('user_id',$userId)->whereHas('resource',$visible)->latest('last_read_at');

        return view('library.mine',[
            'continue'=>(clone $reading)->whereNull('completed_at')->take(12)->get(),
            'history'=>(clone $reading)->paginate(15,['*'],'history_page')->withQueryString()->fragment('history'),
            'bookmarks'=>LibraryResource::with('category')->where($visible)
                ->join('library_bookmarks','library_bookmarks.library_resource_id','=','library_resources.id')
                ->where('library_bookmarks.user_id',$userId)
                ->orderByDesc('library_bookmarks.created_at')
                ->select('library_resources.*','library_bookmarks.created_at as bookmarked_at')
                ->paginate(15,['*'],'bookmarks_page')->withQueryString()->fragment('bookmarks'),
            'requests'=>LibraryDownloadRequest::with('resource')->where('user_id',$userId)->latest()->paginate(15,['*'],'requests_page')->withQueryString()->fragment('requests'),
            'stats'=>[
                'reading'=>(clone $reading)->whereNull('completed_at')->count(),
                'finished'=>(clone $reading)->whereNotNull('completed_at')->count(),
                'bookmarks'=>DB::table('library_bookmarks')->where('user_id',$userId)->count(),
                'requests'=>LibraryDownloadRequest::where('user_id',$userId)->where('status',LibraryDownloadRequest::PENDING)->count(),
            ],
        ]);
    }

    private function applyAccessScope($query): void
    {
        if(!auth()->check()){
            $query->where('access_level','public');
            return;
        }

        if(!auth()->user()->isStaff()){
            $query->whereIn('access_level',['public','authenticated']);
        }
    }

    private function authoriseAccess(LibraryResource $resource): void
    {
        abort_unless($resource->is_active,404);

        if($resource->access_level==='public'){
            return;
        }

        abort_unless(auth()->check(),401);

        if($resource->access_level==='staff'){
            abort_unless(auth()->user()->isStaff(),403);
        }
    }
}
