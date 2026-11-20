<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A document every staff member must submit to HR (national ID, certificates, tax PIN...). */
class HrDocumentRequirement extends Model
{
    protected $fillable = ['name', 'description', 'is_mandatory', 'is_active', 'due_days', 'position'];

    protected $attributes = ['is_mandatory' => true, 'is_active' => true, 'due_days' => 14];

    protected $casts = ['is_mandatory' => 'boolean', 'is_active' => 'boolean'];

    public function documents() { return $this->hasMany(StaffDocument::class, 'requirement_id'); }
}
