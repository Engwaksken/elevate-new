@extends('layouts.app')
@section('content')
<div class="card"><h1>System Settings</h1>
<form method="POST" action="{{ route('admin.settings.store') }}">@csrf
<label>Group</label><input name="group" value="general">
<label>Key</label><input name="key" required>
<label>Type</label><select name="type"><option value="string">String</option><option value="boolean">Boolean</option><option value="integer">Integer</option><option value="float">Float</option><option value="json">JSON</option></select>
<label>Value</label><textarea name="value"></textarea>
<label><input style="width:auto" type="checkbox" name="is_public" value="1"> Public setting</label><br>
<label><input style="width:auto" type="checkbox" name="is_encrypted" value="1"> Encrypt value</label><br><br>
<button>Save Setting</button>
</form></div>
@foreach($settings as $setting)
<div class="card"><strong>{{ $setting->group }} / {{ $setting->key }}</strong><br>{{ $setting->is_encrypted ? '[encrypted]' : $setting->value }}</div>
@endforeach
{{ $settings->links() }}
@endsection
