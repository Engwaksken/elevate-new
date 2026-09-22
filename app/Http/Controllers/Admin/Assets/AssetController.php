<?php
namespace App\Http\Controllers\Admin\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\AssetCodeService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query=Asset::latest();

        if($status=$request->get('status')) $query->where('status',$status);
        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q->where('asset_code','like',"%{$search}%")
                ->orWhere('asset_tag','like',"%{$search}%")
                ->orWhere('serial_number','like',"%{$search}%")
                ->orWhere('description','like',"%{$search}%"));
        }

        return view('admin.assets.index',[
            'assets'=>$query->paginate(25)->withQueryString(),
            'categories'=>AssetCategory::where('is_active',true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AssetCodeService $codes)
    {
        $data=$request->validate([
            'asset_tag'=>['nullable','string','max:100','unique:assets,asset_tag'],
            'asset_category_id'=>['nullable','exists:asset_categories,id'],
            'description'=>['required','string','max:255'],
            'brand'=>['nullable','string','max:100'],
            'model'=>['nullable','string','max:100'],
            'serial_number'=>['nullable','string','max:190'],
            'purchase_date'=>['nullable','date'],
            'purchase_price'=>['nullable','numeric','min:0'],
            'currency'=>['nullable','string','size:3'],
            'supplier_id'=>['nullable','exists:suppliers,id'],
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'funding_source'=>['nullable','string','max:190'],
            'location'=>['nullable','string','max:190'],
            'condition'=>['nullable','string','max:100'],
            'warranty_end_date'=>['nullable','date'],
            'notes'=>['nullable','string'],
        ]);

        Asset::create($data+[
            'asset_code'=>$codes->next(),
            'status'=>'available'
        ]);

        return back()->with('success','Asset registered.');
    }
}
