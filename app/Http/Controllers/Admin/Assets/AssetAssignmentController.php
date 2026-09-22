<?php
namespace App\Http\Controllers\Admin\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetAssignmentController extends Controller
{
    public function assign(Request $request, Asset $asset)
    {
        abort_unless(in_array($asset->status,['available','in_use'],true),422);

        $data=$request->validate([
            'assigned_to_user_id'=>['required','exists:users,id'],
            'assigned_date'=>['required','date'],
            'expected_return_date'=>['nullable','date','after_or_equal:assigned_date'],
            'assignment_notes'=>['nullable','string'],
        ]);

        DB::transaction(function() use($asset,$data){
            $asset->assignments()->create($data+[
                'assigned_by'=>auth()->id(),
                'status'=>'active',
            ]);

            $asset->update([
                'custodian_user_id'=>$data['assigned_to_user_id'],
                'status'=>'assigned',
            ]);
        });

        return back()->with('success','Asset assigned.');
    }

    public function return(Request $request, AssetAssignment $assignment)
    {
        $data=$request->validate([
            'returned_date'=>['required','date'],
            'return_condition'=>['nullable','string'],
        ]);

        DB::transaction(function() use($assignment,$data){
            $assignment->update($data+[
                'received_back_by'=>auth()->id(),
                'status'=>'returned',
            ]);

            $assignment->asset()->update([
                'custodian_user_id'=>null,
                'condition'=>$data['return_condition'] ?: $assignment->asset->condition,
                'status'=>'available',
            ]);
        });

        return back()->with('success','Asset returned.');
    }
}
