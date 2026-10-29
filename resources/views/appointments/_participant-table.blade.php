{{-- Participant appointments table. Expects $rows (appointments) and $empty (message). --}}
<div class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Date and time</th><th>Topic</th><th>Instructor and course</th><th>Mode</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
            <tbody>
            @forelse($rows as $appointment)
                @include('appointments._participant-card', ['appointment' => $appointment])
            @empty
                <tr><td colspan="6"><div class="admin-empty">{{ $empty }}</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@foreach($rows as $appointment)
    @include('appointments._participant-modals', ['appointment' => $appointment])
@endforeach
