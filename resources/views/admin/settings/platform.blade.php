@extends('layouts.admin')
@section('title','Platform Configuration | ElevateHer360')
@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">System Administration</span>
        <h1>Platform Configuration</h1>
        <p>Branding, appearance, backups, storage and maintenance.</p>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $error)<div>{{$error}}</div>@endforeach</div>@endif

<div class="appraisal-admin-kra-tabs">
<button class="appraisal-admin-kra-tab active" type="button" data-config-tab="branding">Branding & Theme</button>
<button class="appraisal-admin-kra-tab" type="button" data-config-tab="backup">Backup & Storage</button>
<button class="appraisal-admin-kra-tab" type="button" data-config-tab="maintenance">Maintenance</button>
</div>

<section data-config-panel="branding">
<div class="admin-panel">
<form method="POST" enctype="multipart/form-data" action="{{route('admin.platform-settings.branding')}}">
@csrf @method('PUT')
<div class="eh-form-grid">
<div><label>System Name</label><input name="system_name" value="{{$settings->get('branding.system_name','ElevateHer360')}}" placeholder="System name"></div>
<div><label>Short Name</label><input name="short_name" value="{{$settings->get('branding.short_name','E360')}}" placeholder="Short name"></div>
<div><label>Primary Colour</label><input type="color" name="primary_color" value="{{$settings->get('branding.primary_color','#800000')}}"></div>
<div><label>Secondary Colour</label><input type="color" name="secondary_color" value="{{$settings->get('branding.secondary_color','#ffffff')}}"></div>
<div><label>Accent Colour</label><input type="color" name="accent_color" value="{{$settings->get('branding.accent_color','#D4AF37')}}"></div>
<div><label>Font Family</label><input name="font_family" value="{{$settings->get('branding.font_family','DM Sans')}}" placeholder="e.g. DM Sans"></div>
<div><label>Base Font Size</label><input type="number" min="12" max="22" name="font_size" value="{{$settings->get('branding.font_size',16)}}"></div>
<div><label>Logo</label><input type="file" name="logo" accept="image/*"></div>
<div><label>Favicon</label><input type="file" name="favicon" accept=".png,.ico,.jpg,.jpeg,.webp"></div>
</div>
<button class="btn btn-primary">Save Branding</button>
</form>
</div>
</section>

<section data-config-panel="backup" hidden>
<div class="admin-panel">
<h2>Backup & Storage Configuration</h2>
<form method="POST" action="{{route('admin.platform-settings.storage')}}">
@csrf @method('PUT')
<div class="eh-form-grid">
<div><label>Backup Destination</label><select name="backup_destination"><option value="local" @selected($settings->get('backup.destination','local')==='local')>Local server</option><option value="google" @selected($settings->get('backup.destination')==='google')>Google storage</option></select></div>
<div><label>Retention Days</label><input type="number" name="retention_days" min="1" max="3650" value="{{$settings->get('backup.retention_days',30)}}"></div>
<div><label>Google Project ID</label><input name="google_project_id" value="{{$settings->get('backup.google_project_id')}}" placeholder="Google project ID"></div>
<div><label>Google Bucket</label><input name="google_bucket" value="{{$settings->get('backup.google_bucket')}}" placeholder="Bucket name"></div>
<div class="full"><label>Google Service Account JSON</label><textarea name="google_credentials_json" placeholder="Paste new credentials JSON only when changing credentials"></textarea></div>
</div>
<button class="btn btn-primary">Save Storage Configuration</button>
</form>
</div>

<div class="admin-panel">
<div class="admin-panel-head"><div><h2>Backup History</h2><p>Google configuration is stored encrypted; cloud upload requires the server adapter.</p></div>
<form method="POST" action="{{route('admin.platform-settings.backup-now')}}">@csrf<button class="btn btn-primary"><i class="fas fa-database"></i> Backup Now</button></form></div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Date</th><th>Destination</th><th>File</th><th>Size</th><th>Status</th></tr></thead><tbody>
@forelse($backups as $backup)<tr><td>{{$backup->created_at?->format('d M Y H:i')}}</td><td>{{ucfirst($backup->destination)}}</td><td>{{$backup->filename}}</td><td>{{$backup->size_bytes ? number_format($backup->size_bytes/1024/1024,2).' MB' : '—'}}</td><td>{{ucfirst($backup->status)}}</td></tr>@empty<tr><td colspan="5">No backup records.</td></tr>@endforelse
</tbody></table></div>
</div>
</section>

<section data-config-panel="maintenance" hidden>
<div class="admin-panel">
<form method="POST" action="{{route('admin.platform-settings.maintenance')}}">
@csrf @method('PUT')
<label><input type="checkbox" name="enabled" value="1" @checked($settings->get('maintenance.enabled',false))> Enable Maintenance Mode</label>
<label>Title</label><input name="title" value="{{$settings->get('maintenance.title','Scheduled Maintenance')}}" placeholder="Maintenance title">
<label>Message</label><textarea name="message" placeholder="Maintenance message">{{$settings->get('maintenance.message')}}</textarea>
<label>Expected Return</label><input type="datetime-local" name="return_at" value="{{$settings->get('maintenance.return_at')}}">
<button class="btn btn-primary">Save Maintenance Settings</button>
</form>
</div>
</section>

<script>
document.addEventListener('DOMContentLoaded',()=>{
 const tabs=[...document.querySelectorAll('[data-config-tab]')];
 const panels=[...document.querySelectorAll('[data-config-panel]')];
 tabs.forEach(t=>t.onclick=()=>{
   tabs.forEach(x=>x.classList.remove('active'));
   panels.forEach(x=>x.hidden=true);
   t.classList.add('active');
   document.querySelector(`[data-config-panel="${t.dataset.configTab}"]`).hidden=false;
 });
});
</script>
@endsection
