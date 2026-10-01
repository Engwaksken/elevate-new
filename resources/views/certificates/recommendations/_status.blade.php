@php
    $statusLabels = ['pending' => 'Awaiting review', 'approved' => 'Approved · issued', 'rejected' => 'Not approved'];
@endphp
<span class="cert-status cert-status-{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
