<?php

namespace App\Http\Controllers\Admin\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query=Supplier::orderBy('name');

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q
                ->where('name','like',"%{$search}%")
                ->orWhere('category','like',"%{$search}%")
                ->orWhere('tin','like',"%{$search}%")
                ->orWhere('contact_person','like',"%{$search}%")
                ->orWhere('email','like',"%{$search}%"));
        }

        if($status=$request->get('status')) $query->where('status',$status);

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.procurement.suppliers.index',[
            'suppliers'=>$query->paginate($perPage)->withQueryString(),
            'stats'=>[
                'total'=>Supplier::count(),
                'pending'=>Supplier::where('status','pending')->count(),
                'approved'=>Supplier::where('status','approved')->count(),
                'inactive'=>Supplier::where('status','inactive')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'name'=>['required','string','max:190'],
            'category'=>['nullable','string','max:150'],
            'registration_number'=>['nullable','string','max:100'],
            'tin'=>['nullable','string','max:100'],
            'contact_person'=>['nullable','string','max:190'],
            'phone'=>['nullable','string','max:30'],
            'email'=>['nullable','email'],
            'address'=>['nullable','string','max:255'],
            'bank_name'=>['nullable','string','max:190'],
            'bank_account_name'=>['nullable','string','max:190'],
            'bank_account_number'=>['nullable','string','max:100'],
        ]);

        Supplier::create([
            ...collect($data)->except('bank_account_number')->all(),
            'bank_account_number_encrypted'=>$data['bank_account_number'] ?? null,
            'status'=>'pending',
        ]);

        return back()->with('success','Supplier created.');
    }

    public function approve(Supplier $supplier)
    {
        $supplier->update(['status'=>'approved']);

        return back()->with('success','Supplier approved.');
    }
}
