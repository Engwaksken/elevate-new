<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryCategory;
use App\Models\LibraryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LibraryResourceController extends Controller
{
    public function index(Request $request)
    {
        $query=LibraryResource::with('category')->latest();

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

        if($access=$request->get('access_level')){
            $query->where('access_level',$access);
        }

        if($language=$request->get('language')){
            $query->where('language',$language);
        }

        if($request->filled('status')){
            $query->where('is_active',$request->get('status')==='active');
        }

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page')
            : 20;

        return view('admin.library.index',[
            'resources'=>$query->paginate($perPage)->withQueryString(),
            'categories'=>LibraryCategory::where('is_active',true)->orderBy('name')->get(),
            'languages'=>LibraryResource::query()
                ->whereNotNull('language')
                ->select('language')
                ->distinct()
                ->orderBy('language')
                ->pluck('language'),
            'stats'=>[
                'total'=>LibraryResource::count(),
                'active'=>LibraryResource::where('is_active',true)->count(),
                'public'=>LibraryResource::where('access_level','public')->count(),
                'downloads'=>LibraryResource::sum('downloads_count'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data=$this->validated($request);

        $filePath=$this->storeFile($request,'file','library');
        $coverPath=$this->storeFile($request,'cover_image','library/covers');

        LibraryResource::create([
            'library_category_id'=>$data['library_category_id'] ?? null,
            'title'=>$data['title'],
            'author'=>$data['author'] ?? null,
            'description'=>$data['description'] ?? null,
            'tags'=>$this->tags($data['tags_text'] ?? null),
            'cover_image_path'=>$coverPath,
            'file_path'=>$filePath,
            'external_url'=>$data['external_url'] ?? null,
            'language'=>$data['language'],
            'publication_date'=>$data['publication_date'] ?? null,
            'access_level'=>$data['access_level'],
            'is_active'=>$request->boolean('is_active',true),
            'created_by'=>auth()->id(),
        ]);

        return back()->with('success','Library resource created.');
    }

    public function update(Request $request, LibraryResource $resource)
    {
        $data=$this->validated($request,$resource);

        $filePath=$resource->file_path;
        $coverPath=$resource->cover_image_path;

        if($request->hasFile('file')){
            $filePath=$this->storeFile($request,'file','library');
        }

        if($request->hasFile('cover_image')){
            $coverPath=$this->storeFile($request,'cover_image','library/covers');
        }

        $resource->update([
            'library_category_id'=>$data['library_category_id'] ?? null,
            'title'=>$data['title'],
            'author'=>$data['author'] ?? null,
            'description'=>$data['description'] ?? null,
            'tags'=>$this->tags($data['tags_text'] ?? null),
            'cover_image_path'=>$coverPath,
            'file_path'=>$filePath,
            'external_url'=>$data['external_url'] ?? null,
            'language'=>$data['language'],
            'publication_date'=>$data['publication_date'] ?? null,
            'access_level'=>$data['access_level'],
            'is_active'=>$request->boolean('is_active'),
        ]);

        return back()->with('success','Library resource updated.');
    }

    public function destroy(LibraryResource $resource)
    {
        $resource->delete();

        return back()->with('success','Library resource archived.');
    }

    private function validated(Request $request, ?LibraryResource $resource=null): array
    {
        return $request->validate([
            'library_category_id'=>['nullable','exists:library_categories,id'],
            'title'=>['required','string','max:190'],
            'author'=>['nullable','string','max:190'],
            'description'=>['nullable','string'],
            'tags_text'=>['nullable','string','max:2000'],
            'external_url'=>['nullable','url'],
            'language'=>['required','string','max:50'],
            'publication_date'=>['nullable','date'],
            'access_level'=>['required',Rule::in(['public','authenticated','staff'])],
            'is_active'=>['nullable','boolean'],
            'file'=>['nullable','file','max:51200','mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,mp4,mp3,zip'],
            'cover_image'=>['nullable','image','max:5120','mimes:jpg,jpeg,png,webp'],
        ]);
    }

    private function storeFile(Request $request,string $field,string $folder): ?string
    {
        if(!$request->hasFile($field)){
            return null;
        }

        $upload=$request->file($field);
        $name=Str::uuid().'.'.$upload->getClientOriginalExtension();

        return $upload->storeAs($folder,$name,'local');
    }

    private function tags(?string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn($tag)=>trim($tag),
            explode(',',(string)$value)
        ))));
    }
}
