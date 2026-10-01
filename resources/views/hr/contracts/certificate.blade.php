<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Signature Certificate</title>
<style>
    @page { margin: 36px 42px; }
    body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 11px; }
    h1 { margin: 0 0 4px; font-size: 20px; color: #800000; }
    .sub { margin: 0 0 22px; color: #667085; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
    th, td { padding: 7px 9px; border: 1px solid #e4e7ec; text-align: left; vertical-align: top; }
    th { width: 34%; background: #f9fafb; color: #475467; font-weight: bold; }
    .signature { padding: 14px; border: 1px solid #d0d5dd; text-align: center; }
    .signature img { max-width: 320px; max-height: 140px; }
    .signature p { margin: 8px 0 0; color: #667085; }
    .hash { font-family: DejaVu Sans Mono, monospace; font-size: 9px; word-break: break-all; }
    .foot { margin-top: 26px; color: #98a2b3; font-size: 9px; }
</style>
</head>
<body>
<h1>Electronic Signature Certificate</h1>
<p class="sub">ElevateHer360 · Employment contract #{{ $contract->id }}</p>

<table>
<tr><th>Employee</th><td>{{ $contract->employee?->user?->name }} ({{ $contract->employee?->user?->email }})</td></tr>
<tr><th>Employee number</th><td>{{ $contract->employee?->employee_number }}</td></tr>
<tr><th>Contract type</th><td>{{ $contract->contract_type ?: 'Employment contract' }}</td></tr>
<tr><th>Contract period</th><td>{{ optional($contract->start_date)->format('d M Y') }} – {{ optional($contract->end_date)->format('d M Y') ?: 'Open-ended' }}</td></tr>
@if($contract->gross_salary !== null)
<tr><th>Gross salary</th><td>{{ $contract->currency }} {{ number_format((float) $contract->gross_salary, 2) }}</td></tr>
@endif
<tr><th>Contract document</th><td>{{ $contract->documentName() }}</td></tr>
@if($documentHash)
<tr><th>Document SHA-256</th><td class="hash">{{ $documentHash }}</td></tr>
@endif
<tr><th>Sent for signature</th><td>{{ $contract->sent_for_signature_at?->format('d M Y H:i:s T') ?: '—' }}{{ $contract->sender ? ' by '.$contract->sender->name : '' }}</td></tr>
<tr><th>Signed by</th><td>{{ $contract->signer?->name }}</td></tr>
<tr><th>Signed at</th><td>{{ $contract->signed_at?->format('d M Y H:i:s T') }}</td></tr>
<tr><th>Signature method</th><td>{{ $contract->signature_method === 'drawn' ? 'Drawn on screen' : 'Uploaded signature image' }}</td></tr>
<tr><th>Signer IP address</th><td>{{ $contract->signer_ip ?: '—' }}</td></tr>
@if($contract->signer_comment)
<tr><th>Signer comment</th><td>{{ $contract->signer_comment }}</td></tr>
@endif
</table>

<div class="signature">
<img src="{{ $signatureDataUri }}" alt="Signature">
<p>Signed electronically by {{ $contract->signer?->name }} on {{ $contract->signed_at?->format('d M Y \a\t H:i:s') }}</p>
</div>

<p class="foot">This certificate records the electronic signature applied to the contract document identified above. Generated {{ now()->format('d M Y H:i:s T') }}.</p>
</body>
</html>
