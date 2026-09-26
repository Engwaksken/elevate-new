@extends('layouts.admin')
@section('title','Platform Configuration | ElevateHer360')

@section('content')
@php
    $logoPath = $settings->get('branding.logo_path');
    $faviconPath = $settings->get('branding.favicon_path');
    $logoUrl = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : null;
    $faviconUrl = $faviconPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($faviconPath) : null;
    $activeConfigTab = session('platform_settings_tab', 'branding');
@endphp

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">System Administration</span>
        <h1>Platform Configuration</h1>
        <p>Branding, appearance, backups, storage and maintenance.</p>
    </div>
</div>

<style>
.eh-brand-assets{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin:16px 0 20px}
.eh-brand-asset-card{border:1px solid #e6e6e8;border-radius:12px;padding:14px;background:#fff}
.eh-brand-asset-card h3{margin:0 0 4px;font-size:15px}
.eh-brand-asset-card p{margin:0 0 12px;color:#6b7077;font-size:13px}
.eh-brand-preview{min-height:118px;display:flex;align-items:center;justify-content:center;border:1px dashed #cfd1d5;border-radius:10px;background:#fafafb;overflow:hidden}
.eh-brand-preview img{display:block;max-width:100%;max-height:92px;object-fit:contain}
.eh-brand-preview--favicon img{width:48px;height:48px}
.eh-brand-preview-empty{color:#848990;font-size:13px;text-align:center;padding:20px}
.eh-brand-file-input{margin-top:12px}
@media(max-width:720px){.eh-brand-assets{grid-template-columns:1fr}}
</style>

<div class="appraisal-admin-kra-tabs">
    <button class="appraisal-admin-kra-tab" type="button" data-config-tab="branding">Branding & Theme</button>
    <button class="appraisal-admin-kra-tab" type="button" data-config-tab="backup">Backup & Storage</button>
    <button class="appraisal-admin-kra-tab" type="button" data-config-tab="maintenance">Maintenance</button>
</div>

<section data-config-panel="branding">
<div class="admin-panel">
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.platform-settings.branding') }}">
@csrf
@method('PUT')

<div class="eh-form-grid">
    <div><label>System Name</label><input name="system_name" value="{{ old('system_name',$settings->get('branding.system_name','ElevateHer360')) }}" placeholder="System name" required></div>
    <div><label>Short Name</label><input name="short_name" value="{{ old('short_name',$settings->get('branding.short_name','E360')) }}" placeholder="Short name"></div>
    <div><label>Primary Colour</label><input type="color" name="primary_color" value="{{ old('primary_color',$settings->get('branding.primary_color','#800000')) }}"></div>
    <div><label>Secondary Colour</label><input type="color" name="secondary_color" value="{{ old('secondary_color',$settings->get('branding.secondary_color','#ffffff')) }}"></div>
    <div><label>Accent Colour</label><input type="color" name="accent_color" value="{{ old('accent_color',$settings->get('branding.accent_color','#D4AF37')) }}"></div>
    <div><label>Font Family</label><input name="font_family" value="{{ old('font_family',$settings->get('branding.font_family','DM Sans')) }}" placeholder="e.g. DM Sans"></div>
    <div><label>Base Font Size</label><input type="number" min="12" max="22" name="font_size" value="{{ old('font_size',$settings->get('branding.font_size',16)) }}"></div>
</div>

<div class="eh-brand-assets">
    <div class="eh-brand-asset-card">
        <h3>Logo</h3>
        <p>The saved logo is used across the platform navigation.</p>
        <div class="eh-brand-preview" data-image-preview="logo">
            @if($logoUrl)
                <img src="{{ $logoUrl }}?v={{ md5((string)$logoPath) }}" alt="Current platform logo" data-preview-image>
            @else
                <span class="eh-brand-preview-empty" data-preview-empty>No logo uploaded yet.</span>
                <img src="" alt="Selected platform logo preview" data-preview-image hidden>
            @endif
        </div>
        <input class="eh-brand-file-input" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.gif,image/png,image/jpeg,image/webp,image/gif" data-image-input="logo">
    </div>

    <div class="eh-brand-asset-card">
        <h3>Favicon</h3>
        <p>The favicon appears in browser tabs and bookmarks.</p>
        <div class="eh-brand-preview eh-brand-preview--favicon" data-image-preview="favicon">
            @if($faviconUrl)
                <img src="{{ $faviconUrl }}?v={{ md5((string)$faviconPath) }}" alt="Current favicon" data-preview-image>
            @else
                <span class="eh-brand-preview-empty" data-preview-empty>No favicon uploaded yet.</span>
                <img src="" alt="Selected favicon preview" data-preview-image hidden>
            @endif
        </div>
        <input class="eh-brand-file-input" type="file" name="favicon" accept=".png,.ico,.jpg,.jpeg,.webp,image/png,image/x-icon,image/jpeg,image/webp" data-image-input="favicon">
    </div>
</div>

<button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Branding</button>
</form>
</div>
</section>

<section data-config-panel="backup" hidden>
<div class="admin-panel">
<h2>Backup & Storage Configuration</h2>
<form method="POST" action="{{ route('admin.platform-settings.storage') }}">
@csrf
@method('PUT')
<div class="eh-form-grid">
    <div><label>Backup Destination</label><select name="backup_destination"><option value="local" @selected($settings->get('backup.destination','local')==='local')>Local server</option><option value="google" @selected($settings->get('backup.destination')==='google')>Google storage</option></select></div>
    <div><label>Retention Days</label><input type="number" name="retention_days" min="1" max="3650" value="{{ $settings->get('backup.retention_days',30) }}"></div>
    <div><label>Google Project ID</label><input name="google_project_id" value="{{ $settings->get('backup.google_project_id') }}" placeholder="Google project ID"></div>
    <div><label>Google Bucket</label><input name="google_bucket" value="{{ $settings->get('backup.google_bucket') }}" placeholder="Bucket name"></div>
    <div class="full"><label>Google Service Account JSON</label><textarea name="google_credentials_json" placeholder="Paste new credentials JSON only when changing credentials"></textarea></div>
</div>
<button class="btn btn-primary">Save Storage Configuration</button>
</form>
</div>

<div class="admin-panel">
<div class="admin-panel-head">
    <div><h2>Backup History</h2><p>Google configuration is stored encrypted; cloud upload requires the server adapter.</p></div>
    <form method="POST" action="{{ route('admin.platform-settings.backup-now') }}">@csrf<button class="btn btn-primary"><i class="fas fa-database"></i> Backup Now</button></form>
</div>
<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Date</th><th>Destination</th><th>File</th><th>Size</th><th>Status</th></tr></thead>
<tbody>
@forelse($backups as $backup)
<tr>
    <td>{{ $backup->created_at?->format('d M Y H:i') }}</td>
    <td>{{ ucfirst($backup->destination) }}</td>
    <td>{{ $backup->filename }}</td>
    <td>{{ $backup->size_bytes ? number_format($backup->size_bytes/1024/1024,2).' MB' : '—' }}</td>
    <td>{{ ucfirst($backup->status) }}</td>
</tr>
@empty
<tr><td colspan="5">No backup records.</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
</section>

<section data-config-panel="maintenance" hidden>
<div class="admin-panel">
<form method="POST" action="{{ route('admin.platform-settings.maintenance') }}">
@csrf
@method('PUT')
<label><input type="checkbox" name="enabled" value="1" @checked($settings->get('maintenance.enabled',false))> Enable Maintenance Mode</label>
<label>Title</label><input name="title" value="{{ $settings->get('maintenance.title','Scheduled Maintenance') }}" placeholder="Maintenance title">
<label>Message</label><textarea name="message" placeholder="Maintenance message">{{ $settings->get('maintenance.message') }}</textarea>
<label>Expected Return</label><input type="datetime-local" name="return_at" value="{{ $settings->get('maintenance.return_at') }}">
<button class="btn btn-primary">Save Maintenance Settings</button>
</form>
</div>
</section>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    const tabs=[...document.querySelectorAll('[data-config-tab]')];
    const panels=[...document.querySelectorAll('[data-config-panel]')];
    const requestedTab=@json($activeConfigTab);

    const activate=(name)=>{
        const selected=tabs.some((tab)=>tab.dataset.configTab===name)?name:'branding';
        tabs.forEach((tab)=>tab.classList.toggle('active',tab.dataset.configTab===selected));
        panels.forEach((panel)=>panel.hidden=panel.dataset.configPanel!==selected);
    };

    activate(requestedTab);
    tabs.forEach((tab)=>tab.addEventListener('click',()=>activate(tab.dataset.configTab)));

    document.querySelectorAll('[data-image-input]').forEach((input)=>{
        input.addEventListener('change',()=>{
            const file=input.files?.[0];
            if(!file)return;
            const preview=document.querySelector(`[data-image-preview="${input.dataset.imageInput}"]`);
            const image=preview?.querySelector('[data-preview-image]');
            const empty=preview?.querySelector('[data-preview-empty]');
            if(!image)return;
            const reader=new FileReader();
            reader.addEventListener('load',()=>{
                image.src=reader.result;
                image.hidden=false;
                if(empty)empty.hidden=true;
            });
            reader.readAsDataURL(file);
        });
    });
});
</script>
@endsection
