<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;
class SupportSettingsController extends Controller
{
    public function edit(SettingsService $settings){return view('admin.settings.support',['support'=>['email'=>$settings->get('support.email',''),'alternate_email'=>$settings->get('support.alternate_email',''),'phone'=>$settings->get('support.phone',''),'whatsapp'=>$settings->get('support.whatsapp',''),'branch'=>$settings->get('support.branch',''),'address'=>$settings->get('support.address',''),'hours'=>$settings->get('support.hours',''),'introduction'=>$settings->get('support.introduction',''),'technical'=>$settings->get('support.technical','')]]);}
    public function update(Request $request,SettingsService $settings){$data=$request->validate(['email'=>['nullable','email','max:255'],'alternate_email'=>['nullable','email','max:255'],'phone'=>['nullable','string','max:80'],'whatsapp'=>['nullable','string','max:80'],'branch'=>['nullable','string','max:160'],'address'=>['nullable','string','max:500'],'hours'=>['nullable','string','max:255'],'introduction'=>['nullable','string','max:3000'],'technical'=>['nullable','string','max:3000']]);foreach($data as $key=>$value){$settings->set('support.'.$key,$value??'','string','support',true);}return back()->with('success','Help and support details updated.');}
}