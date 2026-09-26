<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $query=SystemSetting::query()->orderBy('group')->orderBy('key');

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('key','like',"%{$search}%")
                    ->orWhere('group','like',"%{$search}%");
            });
        }

        if($group=$request->get('group')){
            $query->where('group',$group);
        }

        return view('admin.settings.index',[
            'settings'=>$query->paginate(25)->withQueryString(),
            'groups'=>SystemSetting::select('group')->distinct()->orderBy('group')->pluck('group'),
            'stats'=>[
                'total'=>SystemSetting::count(),
                'public'=>SystemSetting::where('is_public',true)->count(),
                'encrypted'=>SystemSetting::where('is_encrypted',true)->count(),
                'groups'=>SystemSetting::distinct('group')->count('group'),
            ],
        ]);
    }

    public function store(Request $request, SettingsService $service)
    {
        $data=$this->validated($request);

        $value=$data['type']==='json' && is_string($data['value'])
            ? json_decode($data['value'],true)
            : $data['value'];

        $service->set(
            $data['key'],$value,$data['type'],$data['group'],
            $request->boolean('is_public'),
            $request->boolean('is_encrypted'),
        );

        return back()->with('success','Setting saved.');
    }

    public function update(Request $request, SystemSetting $setting, SettingsService $service)
    {
        $data=$this->validated($request);

        $value=$data['type']==='json' && is_string($data['value'])
            ? json_decode($data['value'],true)
            : $data['value'];

        $service->set(
            $data['key'],$value,$data['type'],$data['group'],
            $request->boolean('is_public'),
            $request->boolean('is_encrypted'),
        );

        if($setting->key!==$data['key']){
            $setting->delete();
        }

        return back()->with('success','Setting updated.');
    }

    public function destroy(SystemSetting $setting)
    {
        $setting->delete();
        return back()->with('success','Setting deleted.');
    }

    private function validated(Request $request): array
    {
        $data=$request->validate([
            'key'=>['required','string','max:190'],
            'group'=>['required','string','max:100'],
            'type'=>['required','in:string,boolean,integer,float,json'],
            'value'=>['nullable'],
            'is_public'=>['nullable','boolean'],
            'is_encrypted'=>['nullable','boolean'],
        ]);

        if($data['type']==='json' && is_string($data['value']) && $data['value']!==''){
            json_decode($data['value'],true);
            if(json_last_error()!==JSON_ERROR_NONE){
                abort(422,'Invalid JSON value.');
            }
        }

        return $data;
    }
}
