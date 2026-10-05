{{-- Bulk delete bar. Expects $bulkRoute and $bulkTableId. --}}
<div class="admin-bulk-bar" id="{{ $bulkTableId }}-bar">
    <strong><span data-selected-count>0</span> selected</strong>
    <form method="POST" action="{{ $bulkRoute }}" data-bulk-form data-table="{{ $bulkTableId }}">
        @csrf @method('DELETE')
        <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete Selected</button>
    </form>
</div>
