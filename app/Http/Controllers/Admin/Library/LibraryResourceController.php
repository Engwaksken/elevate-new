<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryCategory;
use App\Models\LibraryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LibraryResourceController extends Controller
{
    public function index()
    {
        return view('admin.library.index', [
            'resources'=>LibraryResource::with('category')->latest()->paginate(20),
            'categories'=>LibraryCategory::where('is_active',true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'library_category_id'=>['nullable','exists:library_categories,id'],
            'title'=>['required','string','max:190'],
            'author'=>['nullable','string','max:190'],
            'description'=>['nullable','string'],
            'tags_text'=>['nullable','string'],
            'external_url'=>['nullable','url'],
            'language'=>['required','string','max:50'],
            'publication_date'=>['nullable','date'],
            'access_level'=>['required','in:public,authenticated,staff'],
            'file'=>['nullable','file','max:51200','mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,mp4,mp3,zip'],
        ]);

        $path = null;
        if ($request->hasFile('file')) {
            $upload = $request->file('file');
            $path = $upload->storeAs('library',Str::uuid().'.'.$upload->getClientOriginalExtension(),'local');
        }

        LibraryResource::create([
            'library_category_id'=>$data['library_category_id'] ?? null,
            'title'=>$data['title'],
            'author'=>$data['author'] ?? null,
            'description'=>$data['description'] ?? null,
            'tags'=>array_values(array_filter(array_map('trim',explode(',',$data['tags_text'] ?? '')))),
            'file_path'=>$path,
            'external_url'=>$data['external_url'] ?? null,
            'language'=>$data['language'],
            'publication_date'=>$data['publication_date'] ?? null,
            'access_level'=>$data['access_level'],
            'is_active'=>true,
            'created_by'=>auth()->id(),
        ]);

        return back()->with('success','Library resource created.');
    }
}
