@extends('layouts.admin')
@section('title','System Settings | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div>
<span class="admin-eyebrow">System Administration</span>
<h1>System Settings</h1>
<p>Manage grouped platform settings, public configuration and encrypted values.</p>
</div>
<div class="admin-page-actions">
<button type="button" class="btn btn-primary" data-modal-open="createSetting"><i class="fas fa-plus"></i> Add Setting</button>
</div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="admin-stats-grid compact">
@foreach([['total','Settings','fa-gears'],['groups','Groups','fa-layer-group'],['public','Public','fa-globe'],['encrypted','Encrypted','fa-lock']] as [$k,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$k]??0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-search"></i><input name="search" value="{{ request('search') }}" placeholder="Search setting key or group..."></div>
<select name="group"><option value="">All groups</option>@foreach($groups as $group)<option value="{{ $group }}" @selected(request('group')===$group)>{{ ucfirst($group) }}</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button>
<a href="{{ route('admin.settings.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Group</th><th>Key</th><th>Type</th><th>Value</th><th>Public</th><th>Encrypted</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($settings as $setting)
<tr>
<td>{{ $setting->group }}</td>
<td><strong>{{ $setting->key }}</strong></td>
<td>{{ ucfirst($setting->type) }}</td>
<td>{{ $setting->is_encrypted ? '••••••••' : Str::limit((string)$setting->value,60) }}</td>
<td>{{ $setting->is_public ? 'Yes':'No' }}</td>
<td>{{ $setting->is_encrypted ? 'Yes':'No' }}</td>
<td class="table-actions"><div class="action-group">
<button type="button" class="btn-icon" data-modal-open="editSetting{{ $setting->id }}"><i class="fas fa-pen"></i></button>
<button type="button" class="btn-icon danger" data-modal-open="deleteSetting{{ $setting->id }}"><i class="fas fa-trash"></i></button>
</div></td>
</tr>
@empty<tr><td colspan="7"><div class="admin-empty">No system settings found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $settings->links() }}</div>
</div>

@php($blank=new \App\Models\SystemSetting(['group'=>'general','type'=>'string']))
@include('admin.settings.modal',['id'=>'createSetting','title'=>'Add Setting','setting'=>$blank,'action'=>route('admin.settings.store'),'method'=>'POST'])
@foreach($settings as $setting)
@include('admin.settings.modal',['id'=>'editSetting'.$setting->id,'title'=>'Edit Setting','setting'=>$setting,'action'=>route('admin.settings.update',$setting),'method'=>'PUT'])
<div class="eh-modal" id="deleteSetting{{ $setting->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Delete Setting?</h2><p>{{ $setting->group }} / {{ $setting->key }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>Delete this configuration only if no application feature depends on it.</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.settings.destroy',$setting) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete</button></form></div>
</div></div>
@endforeach
@endsection
