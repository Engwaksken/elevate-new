<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.index',[
            'settings'=>SystemSetting::orderBy('group')->orderBy('key')->paginate(50)
        ]);
    }

    public function store(Request $request, SettingsService $service)
    {
        $data=$request->validate([
            'key'=>['required','string','max:190'],
            'group'=>['required','string','max:100'],
            'type'=>['required','in:string,boolean,integer,float,json'],
            'value'=>['nullable'],
            'is_public'=>['nullable','boolean'],
            'is_encrypted'=>['nullable','boolean'],
        ]);

        $value=$data['type']==='json' && is_string($data['value'])
            ? json_decode($data['value'],true)
            : $data['value'];

        $service->set(
            $data['key'],
            $value,
            $data['type'],
            $data['group'],
            $request->boolean('is_public'),
            $request->boolean('is_encrypted'),
        );

        return back()->with('success','Setting saved.');
    }
}
