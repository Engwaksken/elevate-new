<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    protected $fillable = ['slug', 'title', 'summary', 'body', 'settings', 'image_path', 'published_data', 'published_at', 'updated_by'];
    protected $casts = ['settings' => 'array', 'published_data' => 'array', 'published_at' => 'datetime'];

    public function draft(): array
    {
        return $this->only(['title', 'summary', 'body', 'settings', 'image_path']);
    }
}
