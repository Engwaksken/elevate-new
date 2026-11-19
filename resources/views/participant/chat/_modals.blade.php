@php
    $viewerId = (int) auth()->id();
    $manageable = collect($conversations instanceof \Illuminate\Contracts\Pagination\Paginator ? $conversations->items() : $conversations)
        ->when(isset($conversation), fn ($list) => $list->push($conversation))
        ->unique('id')
        ->filter(fn ($item) => (int) $item->created_by === $viewerId);
    $createType = old('type', 'direct');
    $createFailed = old('type') !== null && $errors->any();
    $isStaffChat = $chatRoute === 'admin.chat';
    $createIntro = $isStaffChat
        ? 'Message a colleague, or bring several people together in a group. Instructors can also add participants from their courses.'
        : 'Message another participant or one of your course instructors, or bring several people together in a group.';
    $oldPeople = fn ($ids) => \App\Models\User::query()->whereIn('id', array_map('intval', (array) $ids))->get(['id', 'name'])
        ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'meta' => ''])->all();
@endphp

<div class="eh-modal" id="chat-create" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-create-title" @if($createFailed) data-modal-autoopen @endif>
    <div class="eh-modal-dialog"><form method="POST" action="{{ route($chatRoute.'.store') }}">@csrf
        <div class="eh-modal-header"><div><h2 id="chat-create-title">New conversation</h2><p>{{ $createIntro }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
        <div class="eh-modal-body">
            @if($createFailed)<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
            <div class="ehc-seg" role="radiogroup" aria-label="Conversation type">
                <label><input type="radio" name="type" value="direct" data-chat-create-type @checked($createType === 'direct')><i class="fas fa-user" aria-hidden="true"></i> Direct message</label>
                <label><input type="radio" name="type" value="group" data-chat-create-type @checked($createType === 'group')><i class="fas fa-user-group" aria-hidden="true"></i> Group</label>
            </div>
            <div data-chat-create-panel="direct">
                <span class="ehc-label">Person</span>
                <x-people-picker id="chat-direct-person" name="participant_id" :multiple="false" :required="true"
                    :source="route($chatRoute.'.people')" :selected="old('participant_id') ? $oldPeople([old('participant_id')]) : []" />
                <span class="ehc-hint">If you already have a chat with this person, it will open.</span>
            </div>
            <div data-chat-create-panel="group">
                <label class="ehc-label" for="chat-group-title">Group name</label>
                <input class="ehc-field" id="chat-group-title" name="title" maxlength="120" value="{{ old('title') }}" placeholder="e.g. Digital Skills Cohort" data-chat-required>
                <span class="ehc-label">Members</span>
                <x-people-picker id="chat-group-members" name="participant_ids" :required="true" :groups="$chatGroups"
                    :source="route($chatRoute.'.people')" :selected="$oldPeople(old('participant_ids', []))" />
                <span class="ehc-hint" id="chat-group-help">Search and click to add people. You are added automatically.</span>
            </div>
        </div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane" aria-hidden="true"></i> Start conversation</button></div>
    </form></div>
</div>

@foreach($manageable as $item)
    @if($item->type === 'group')
        <div class="eh-modal" id="chat-edit-{{ $item->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-edit-{{ $item->id }}-title"><div class="eh-modal-dialog"><form method="POST" action="{{ route($chatRoute.'.update', $item) }}">@csrf @method('PUT')
            <div class="eh-modal-header"><div><h2 id="chat-edit-{{ $item->id }}-title">Rename group</h2><p>Everyone in the group will see the new name.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
            <div class="eh-modal-body"><label class="ehc-label" for="chat-title-{{ $item->id }}">Group name</label><input class="ehc-field" id="chat-title-{{ $item->id }}" name="title" maxlength="120" required value="{{ $item->title }}"></div>
            <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save changes</button></div>
        </form></div></div>
    @endif
    <div class="eh-modal" id="chat-delete-{{ $item->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-delete-{{ $item->id }}-title"><div class="eh-modal-dialog"><form method="POST" action="{{ route($chatRoute.'.destroy', $item) }}">@csrf @method('DELETE')
        <div class="eh-modal-header"><div><h2 id="chat-delete-{{ $item->id }}-title">Delete conversation?</h2><p>All of its messages are permanently deleted for every member.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Keep conversation</button><button class="btn btn-danger" type="submit">Delete conversation</button></div>
    </form></div></div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', () => {
    // New-conversation modal: show the fields for the chosen type only.
    const types = document.querySelectorAll('[data-chat-create-type]');
    const sync = () => {
        const value = [...types].find(t => t.checked)?.value || 'direct';
        document.querySelectorAll('[data-chat-create-panel]').forEach(panel => {
            const active = panel.dataset.chatCreatePanel === value;
            panel.hidden = !active;
            // Disable the hidden panel's fields so they are not posted; only the group name is a native required field.
            panel.querySelectorAll('input,select').forEach(input => { input.disabled = !active; });
            panel.querySelectorAll('[data-chat-required]').forEach(input => { input.required = active; });
        });
    };
    types.forEach(t => t.addEventListener('change', sync));
    sync();

    // Sidebar search filters the conversation list in place.
    const search = document.querySelector('[data-ehc-search]');
    const rows = document.querySelectorAll('[data-ehc-filter]');
    const none = document.querySelector('[data-ehc-noresults]');
    search?.addEventListener('input', () => {
        const term = search.value.trim().toLowerCase();
        let shown = 0;
        rows.forEach(row => { const hit = !term || row.dataset.ehcFilter.includes(term); row.hidden = !hit; shown += hit; });
        if (none) none.hidden = shown > 0;
    });
});
</script>
