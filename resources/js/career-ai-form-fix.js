function normaliseCareerAiText(value = '') {
    return value.toLowerCase().replace(/[_\s]+/g, '-');
}

function isCareerAiPage() {
    const path = normaliseCareerAiText(window.location.pathname);
    if (
        path.includes('career-ai')
        || path.includes('careerai')
        || path.includes('ai-career')
    ) {
        return true;
    }

    const title = normaliseCareerAiText(document.title || '');
    if (title.includes('career-ai') || title.includes('careerai')) {
        return true;
    }

    const headings = Array.from(
        document.querySelectorAll('h1,h2,.page-title,.admin-page-header')
    ).slice(0, 8).map(el => normaliseCareerAiText(el.textContent || ''));

    return headings.some(text =>
        text.includes('career-ai')
        || text.includes('ai-career')
        || text.includes('career-guidance-ai')
    );
}

function careerAiLabel(field) {
    if (field.id) {
        const linked = document.querySelector(`label[for="${CSS.escape(field.id)}"]`);
        if (linked) return linked.textContent.replace(/\*/g, '').trim();
    }

    const group = field.closest(
        '.form-group,.field,.input-group,.mb-3,.mb-4,.eh-field,.career-ai-field,.modal-grid>div,.eh-form-grid>div'
    );

    const label = group?.querySelector('label');
    if (label) return label.textContent.replace(/\*/g, '').trim();

    return (field.name || field.id || 'Field')
        .replace(/\[[^\]]+\]/g, ' ')
        .replace(/[_\-.]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/\b\w/g, char => char.toUpperCase());
}

function careerAiPlaceholder(field, label) {
    const name = (field.name || '').toLowerCase();
    const lower = label.toLowerCase();
    const type = (field.type || '').toLowerCase();

    if (name.includes('career_goal') || lower.includes('career goal')) {
        return 'e.g. Become a junior software developer within the next 12 months';
    }
    if (name.includes('target_role') || lower.includes('target role')) {
        return 'e.g. Junior Data Analyst';
    }
    if (name.includes('current_role') || lower.includes('current role')) {
        return 'e.g. Customer Support Assistant';
    }
    if (name.includes('skill') || lower.includes('skill')) {
        return 'e.g. Communication, Excel, HTML, project coordination';
    }
    if (name.includes('interest') || lower.includes('interest')) {
        return 'e.g. Technology, entrepreneurship, digital marketing';
    }
    if (name.includes('experience') || lower.includes('experience')) {
        return 'Summarise your relevant work, volunteering, projects or practical experience...';
    }
    if (name.includes('education') || lower.includes('education')) {
        return 'e.g. Diploma in Information Technology';
    }
    if (name.includes('location') || lower.includes('location')) {
        return 'e.g. Kampala, Uganda';
    }
    if (name.includes('industry') || lower.includes('industry')) {
        return 'e.g. Technology, Finance, Health, Education';
    }
    if (name.includes('challenge') || lower.includes('challenge')) {
        return 'Describe the main career challenge you want help with...';
    }
    if (name.includes('goal') || lower.includes('goal')) {
        return 'Describe the career outcome you want to achieve...';
    }
    if (name.includes('prompt') || lower.includes('prompt')) {
        return 'Ask Career AI for specific guidance about your career journey...';
    }
    if (type === 'email') return 'e.g. name@example.com';
    if (type === 'url') return 'https://example.com';
    if (field.tagName === 'TEXTAREA') return `Enter ${label.toLowerCase()}...`;

    return `Enter ${label.toLowerCase()}`;
}

function careerAiHint(field, label) {
    const name = (field.name || '').toLowerCase();
    const lower = label.toLowerCase();
    const type = (field.type || '').toLowerCase();
    const prefix = field.required ? 'Required. ' : 'Optional. ';

    if (type === 'file' || name.includes('resume') || name.includes('cv')) {
        return `${prefix}Upload the most recent CV/resume or supporting document requested by this form.`;
    }
    if (name.includes('career_goal') || lower.includes('career goal')) {
        return `${prefix}State a specific role, career direction or outcome you want Career AI to help you plan towards.`;
    }
    if (name.includes('skill') || lower.includes('skill')) {
        return `${prefix}List your current skills separated by commas, including both technical and transferable skills.`;
    }
    if (name.includes('experience') || lower.includes('experience')) {
        return `${prefix}Include paid work, volunteering, internships, projects and practical experience that may be relevant.`;
    }
    if (name.includes('interest') || lower.includes('interest')) {
        return `${prefix}Add areas of work or subjects you genuinely want to explore.`;
    }
    if (name.includes('challenge') || lower.includes('challenge')) {
        return `${prefix}Explain the obstacle clearly so the recommendation can be more relevant.`;
    }
    if (name.includes('prompt') || lower.includes('prompt')) {
        return `${prefix}Be specific about the advice, plan, comparison or next step you want from Career AI.`;
    }
    if (field.tagName === 'SELECT') {
        return `${prefix}Choose the option that best describes your current situation.`;
    }
    if (field.tagName === 'TEXTAREA') {
        return `${prefix}Give enough detail for Career AI to provide a useful recommendation.`;
    }
    if (type === 'number') {
        return `${prefix}Enter a numeric value within the allowed range.`;
    }

    return `${prefix}Provide accurate information so Career AI can personalise the guidance.`;
}

function existingCareerAiHint(field) {
    const group = field.closest(
        '.form-group,.field,.input-group,.mb-3,.mb-4,.eh-field,.career-ai-field,.modal-grid>div,.eh-form-grid>div'
    );

    return Boolean(group?.querySelector(
        '.form-hint,.field-hint,.help-text,.form-text,.text-muted,[data-form-hint]'
    ));
}

function applyCareerAiHelp(form) {
    const fields = form.querySelectorAll(
        'input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]), textarea, select'
    );

    fields.forEach(field => {
        const label = careerAiLabel(field);

        if (
            field.tagName !== 'SELECT'
            && !['date','datetime-local','time','file'].includes((field.type || '').toLowerCase())
            && !field.placeholder
        ) {
            field.placeholder = careerAiPlaceholder(field, label);
        }

        if (!existingCareerAiHint(field)) {
            const hint = document.createElement('small');
            hint.className = 'form-hint career-ai-field-hint';
            hint.dataset.formHint = 'career-ai';
            hint.textContent = careerAiHint(field, label);

            const wrap = field.closest('.password-wrap,.input-group');
            if (wrap && wrap.parentElement) {
                wrap.insertAdjacentElement('afterend', hint);
            } else {
                field.insertAdjacentElement('afterend', hint);
            }
        }
    });
}

function fixCareerAiForms(root = document) {
    if (!isCareerAiPage()) return;

    document.documentElement.classList.add('career-ai-page');

    const forms = root.querySelectorAll?.('form') || [];
    forms.forEach(form => {
        const action = normaliseCareerAiText(form.getAttribute('action') || '');
        const text = normaliseCareerAiText(form.textContent || '');

        if (
            action.includes('career-ai')
            || action.includes('careerai')
            || text.includes('career')
            || document.querySelectorAll('form').length === 1
        ) {
            form.classList.add('career-ai-form-fixed');
            applyCareerAiHelp(form);

            form.querySelectorAll('fieldset').forEach(fieldset => {
                fieldset.classList.add('career-ai-section');
            });

            form.querySelectorAll('textarea').forEach(field => {
                field.closest(
                    '.form-group,.field,.mb-3,.mb-4,.eh-field,.modal-grid>div,.eh-form-grid>div'
                )?.classList.add('career-ai-full');
            });

            form.querySelectorAll('input[type="file"]').forEach(field => {
                field.closest(
                    '.form-group,.field,.mb-3,.mb-4,.eh-field,.modal-grid>div,.eh-form-grid>div'
                )?.classList.add('career-ai-full');
            });
        }
    });

    document.querySelectorAll('.eh-modal,.admin-modal,[data-admin-modal]').forEach(modal => {
        if (modal.querySelector('.career-ai-form-fixed')) {
            modal.classList.add('career-ai-modal-fixed');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    fixCareerAiForms(document);

    const observer = new MutationObserver(mutations => {
        if (!isCareerAiPage()) return;

        for (const mutation of mutations) {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    fixCareerAiForms(node);
                }
            });
        }
    });

    observer.observe(document.body, { childList:true, subtree:true });
});
