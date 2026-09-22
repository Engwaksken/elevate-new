<?php
namespace App\Http\Controllers\Admin\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query=Supplier::orderBy('name');

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q->where('name','like',"%{$search}%")
                ->orWhere('category','like',"%{$search}%")
                ->orWhere('tin','like',"%{$search}%"));
        }

        return view('admin.procurement.suppliers.index',[
            'suppliers'=>$query->paginate(20)->withQueryString()
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
