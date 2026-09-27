<?php
namespace App\Http\Controllers\Participant;
use App\Http\Controllers\Controller;
use App\Services\SettingsService;
class HelpController extends Controller
{
    public function index(SettingsService $settings){return view('participant.help',['support'=>['email'=>$settings->get('support.email',''),'alternate_email'=>$settings->get('support.alternate_email',''),'phone'=>$settings->get('support.phone',''),'whatsapp'=>$settings->get('support.whatsapp',''),'branch'=>$settings->get('support.branch',''),'address'=>$settings->get('support.address',''),'hours'=>$settings->get('support.hours',''),'introduction'=>$settings->get('support.introduction','Contact the ElevateHer360 support team if you need assistance.'),'technical'=>$settings->get('support.technical','')]]);}
}