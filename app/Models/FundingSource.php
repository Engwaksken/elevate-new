<?php
namespace App\Models;

use App\Models\Concerns\SelectableListValue;
use Illuminate\Database\Eloquent\Model;

class FundingSource extends Model
{
    use SelectableListValue;

    protected $fillable=['name','code','description','is_active'];
    protected $casts=['is_active'=>'boolean'];

    public static function textReferences(): array
    {
        return ['purchase_requests'=>'funding_source','assets'=>'funding_source','activities'=>'funding_source'];
    }
}
