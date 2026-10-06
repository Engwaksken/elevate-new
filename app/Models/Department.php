<?php
namespace App\Models;

use App\Models\Concerns\SelectableListValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use SelectableListValue;

    protected $fillable=['name','code','head_user_id','is_active'];
    protected $casts=['is_active'=>'boolean'];

    public static function textReferences(): array
    {
        return ['purchase_requests'=>'department'];
    }

    public static function idReferences(): array
    {
        return ['employees'=>'department_id','positions'=>'department_id'];
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class,'head_user_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
