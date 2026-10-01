<div class="eh-modal" id="contract{{ $employee->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Add Contract</h2><p>{{ data_get($employee,'user.name','Employee') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.hr.contracts.store',$employee) }}" enctype="multipart/form-data">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Contract Type</label><input name="contract_type"></div>
<div class="form-group"><label>Status *</label><select name="status">@foreach(['draft','active','expired','terminated'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="form-group"><label>Start Date *</label><input type="date" name="start_date" required></div>
<div class="form-group"><label>End Date</label><input type="date" name="end_date"></div>
<div class="form-group"><label>Gross Salary</label><input type="number" step=".01" min="0" name="gross_salary"></div>
<div class="form-group"><label>Currency</label><input name="currency" value="UGX" maxlength="3"></div>
<div class="form-group full"><label>Contract File</label><input type="file" name="document" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"><small class="form-hint">PDF, DOC or DOCX, up to 10 MB. Stored privately; only HR and this employee can open it.</small></div>
<div class="form-group full"><label><input type="checkbox" name="send_for_signature" value="1" checked> Send to the employee for review and signature</label><small class="form-hint">Needs a contract file. The employee is notified and signs it from My Contracts.</small></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Add Contract</button></div>
</form></div></div>
