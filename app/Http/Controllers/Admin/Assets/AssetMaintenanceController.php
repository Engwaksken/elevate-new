<?php
namespace App\Http\Controllers\Admin\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Http\Request;

class AssetMaintenanceController extends Controller
{
    public function store(Request $request, Asset $asset)
    {
        $asset->maintenance()->create($request->validate([
            'reported_date'=>['required','date'],
            'maintenance_type'=>['nullable','string','max:100'],
            'issue_description'=>['nullable','string'],
            'supplier_id'=>['nullable','exists:suppliers,id'],
            'cost'=>['nullable','numeric','min:0'],
            'currency'=>['nullable','string','size:3'],
        ]));

        $asset->update(['status'=>'under_maintenance']);

        return back()->with('success','Maintenance record created.');
    }

    public function complete(Request $request, AssetMaintenance $maintenance)
    {
        $maintenance->update($request->validate([
            'completed_date'=>['required','date'],
            'resolution'=>['nullable','string'],
        ])+['status'=>'completed']);

        $maintenance->asset()->update(['status'=>'available']);

        return back()->with('success','Maintenance completed.');
    }
}
