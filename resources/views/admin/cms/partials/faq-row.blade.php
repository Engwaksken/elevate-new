<details class="faq-editor__row" data-faq-row @if($open ?? false) open @endif>
    <summary>
        <span class="faq-editor__num" data-faq-num>{{ is_numeric($i) ? $i + 1 : '' }}</span>
        <span class="faq-editor__q" data-faq-title>{{ ($faq['question'] ?? '') ?: 'New question' }}</span>
        <span class="faq-editor__cat" data-faq-cat>{{ ($faq['category'] ?? '') ?: 'General' }}</span>
        <span class="faq-editor__actions">
            <button type="button" class="btn-icon" title="Move up" data-faq-move="up"><i class="fas fa-arrow-up"></i></button>
            <button type="button" class="btn-icon" title="Move down" data-faq-move="down"><i class="fas fa-arrow-down"></i></button>
            <button type="button" class="btn-icon danger" title="Remove" data-faq-remove><i class="fas fa-trash"></i></button>
        </span>
    </summary>
    <div class="faq-editor__body">
        <div class="faq-editor__grid">
            <div class="form-group"><label>Topic</label><input name="faqs[{{ $i }}][category]" value="{{ $faq['category'] ?? '' }}" list="faq-categories" maxlength="80" data-faq-field="category" placeholder="e.g. Getting started"></div>
            <div class="form-group"><label>Question</label><input name="faqs[{{ $i }}][question]" value="{{ $faq['question'] ?? '' }}" maxlength="300" data-faq-field="question"></div>
        </div>
        <div class="form-group"><label>Answer (Markdown)</label><textarea name="faqs[{{ $i }}][answer]" rows="4" maxlength="5000" data-faq-field="answer">{{ $faq['answer'] ?? '' }}</textarea></div>
    </div>
</details>
