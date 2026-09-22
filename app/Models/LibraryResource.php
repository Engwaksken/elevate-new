<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryResource extends Model
{
    use SoftDeletes;

    protected $fillable=[
        'library_category_id','title','author','description','tags','cover_image_path',
        'file_path','external_url','language','publication_date','access_level',
        'views_count','downloads_count','is_active','created_by'
    ];
    protected $casts=['tags'=>'array','publication_date'=>'date','is_active'=>'boolean'];

    public function category(){ return $this->belongsTo(LibraryCategory::class,'library_category_id'); }
}
