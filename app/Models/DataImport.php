<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class DataImport extends Model {protected $fillable=['module','original_filename','stored_path','column_mapping','total_rows','valid_rows','invalid_rows','imported_rows','status','errors','uploaded_by','confirmed_at']; protected $casts=['column_mapping'=>'array','errors'=>'array','confirmed_at'=>'datetime'];}
