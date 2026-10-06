<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\HrKpiTemplate;
use App\Services\HR\KpiWorkbookImportService;
use Illuminate\Http\Request;

class KpiTemplateController extends Controller
{
    use ExportsTables;
    public function index(Request $request)
    {
        $query=HrKpiTemplate::withCount('items')->latest();

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'KPI & Appraisal Templates',$query,[
                'Template'=>'name',
                'Type'=>fn($t)=>str_replace('_',' ',(string)$t->template_type),
                'Quarter'=>'quarter',
                'Year'=>'year',
                'Items'=>'items_count',
                'Source File'=>'source_file',
                'Source Sheet'=>'source_sheet',
                'Status'=>fn($t)=>$t->is_active?'Active':'Inactive',
                'Uploaded'=>'created_at',
            ]);
        }

        return view('admin.hr.appraisal-templates.index',[
            'templates'=>$query->paginate(20),
        ]);
    }

    public function store(Request $request,KpiWorkbookImportService $service)
    {
        $data=$request->validate([
            'name'=>['required','string','max:190'],
            'template_type'=>['required','in:performance_appraisal,okr_scorecard,behavioral'],
            'quarter'=>['nullable','string','max:20'],
            'year'=>['nullable','integer','min:2020','max:2100'],
            'file'=>['required','file','mimes:xlsx,xls','max:20480'],
        ]);

        $template=$service->import($request->file('file'),$data);

        return back()->with('success',"Template imported with {$template->items->count()} item(s).");
    }

    public function update(Request $request,HrKpiTemplate $template)
    {
        $data=$request->validate([
            'name'=>['required','string','max:190'],
            'template_type'=>['required','in:performance_appraisal,okr_scorecard,behavioral'],
            'quarter'=>['nullable','string','max:20'],
            'year'=>['nullable','integer','min:2020','max:2100'],
            'is_active'=>['nullable','boolean'],
        ]);

        $template->update([
            'name'=>$data['name'],
            'template_type'=>$data['template_type'],
            'quarter'=>$data['quarter'] ?? null,
            'year'=>$data['year'] ?? null,
            'is_active'=>$request->boolean('is_active'),
        ]);

        return back()->with('success','KPI/Appraisal template updated.');
    }

    public function destroy(HrKpiTemplate $template)
    {
        $template->delete();

        return back()->with('success','KPI/Appraisal template removed.');
    }
}
