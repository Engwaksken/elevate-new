<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CareerAiController extends Controller {
    public function index(){
        return redirect()->route('admin.platform-settings.index')->with('platform_settings_tab', 'ai');
    }

    public function update(Request $request){
        app(\App\Services\SystemAiSettingsService::class)->save($request);
        return redirect()->route('admin.platform-settings.index')->with('platform_settings_tab', 'ai')->with('success', 'System AI configuration updated.');
    }
}
