<?php
namespace App\Http\Controllers\Admin\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetDisposalController extends Controller
{
    public function request(Request $request, Asset $asset)
    {
        $asset->disposal()->create($request->validate([
            'requested_date'=>['required','date'],
            'reason'=>['required','string'],
            'disposal_method'=>['nullable','string','max:100'],
            'disposal_value'=>['nullable','numeric','min:0'],
            'currency'=>['nullable','string','size:3'],
        ])+[
            'requested_by'=>auth()->id(),
            'status'=>'requested'
        ]);

        return back()->with('success','Disposal requested.');
    }

    public function approve(Asset $asset)
    {
        $disposal=$asset->disposal()->firstOrFail();
        $disposal->update([
            'status'=>'approved',
            'approved_by'=>auth()->id(),
            'approved_at'=>now(),
        ]);

        return back()->with('success','Disposal approved.');
    }

    public function complete(Asset $asset)
    {
        $disposal=$asset->disposal()->firstOrFail();
        abort_unless($disposal->status==='approved',422);

        $disposal->update([
            'status'=>'completed',
            'completed_at'=>now(),
        ]);
        $asset->update(['status'=>'disposed']);

        return back()->with('success','Asset disposed.');
    }
}
