<div class="eh-assistive-tools" data-eh-assistive-tools>
    <div class="eh-accessibility-panel" id="ehAccessibilityPanel" hidden aria-labelledby="ehAccessibilityTitle">
        <div class="eh-tool-panel-head">
            <div>
                <strong id="ehAccessibilityTitle">Accessibility</strong>
                <small>Adjust the page to suit your reading and navigation needs.</small>
            </div>
            <button type="button" class="eh-tool-close" data-eh-close="accessibility" aria-label="Close accessibility tools">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="eh-accessibility-grid">
            <button type="button" data-a11y-action="text-increase"><i class="fas fa-plus"></i><span>Increase text</span></button>
            <button type="button" data-a11y-action="text-decrease"><i class="fas fa-minus"></i><span>Decrease text</span></button>
            <button type="button" data-a11y-action="contrast"><i class="fas fa-circle-half-stroke"></i><span>High contrast</span></button>
            <button type="button" data-a11y-action="greyscale"><i class="fas fa-droplet-slash"></i><span>Greyscale</span></button>
            <button type="button" data-a11y-action="dyslexia"><i class="fas fa-font"></i><span>Dyslexia-friendly</span></button>
            <button type="button" data-a11y-action="underline"><i class="fas fa-link"></i><span>Underline links</span></button>
            <button type="button" data-a11y-action="motion"><i class="fas fa-person-walking"></i><span>Reduce motion</span></button>
            <button type="button" data-a11y-action="cursor"><i class="fas fa-arrow-pointer"></i><span>Large cursor</span></button>
            <button type="button" data-a11y-action="focus"><i class="fas fa-bullseye"></i><span>Focus highlight</span></button>
            <button type="button" data-a11y-action="guide"><i class="fas fa-ruler-horizontal"></i><span>Reading guide</span></button>
            <button type="button" data-a11y-action="read"><i class="fas fa-volume-high"></i><span>Read page</span></button>
            <button type="button" data-a11y-action="stop-read"><i class="fas fa-volume-xmark"></i><span>Stop reading</span></button>
        </div>

        <div class="eh-tool-panel-footer">
            <button type="button" class="btn btn-outline btn-sm" data-a11y-action="reset">
                <i class="fas fa-rotate-left"></i> Reset accessibility
            </button>
        </div>
    </div>

    <div class="eh-chatbot-panel" id="ehChatbotPanel" hidden aria-labelledby="ehChatbotTitle">
        <div class="eh-tool-panel-head">
            <div>
                <strong id="ehChatbotTitle">ElevateHer360 Assistant</strong>
                <small>Navigation and platform support.</small>
            </div>
            <button type="button" class="eh-tool-close" data-eh-close="chatbot" aria-label="Close chatbot">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="eh-chatbot-quick" aria-label="Suggested questions">
            <button type="button" data-chat-prompt="How do I use accessibility features?">Accessibility</button>
            <button type="button" data-chat-prompt="How do I access my courses?">Learning</button>
            <button type="button" data-chat-prompt="How do I use mentorship?">Mentorship</button>
            <button type="button" data-chat-prompt="How do I find jobs?">Jobs</button>
            <button type="button" data-chat-prompt="How do I use the library?">Library</button>
        </div>

        <div class="eh-chatbot-messages" data-chat-messages role="log" aria-live="polite" aria-relevant="additions">
            <div class="eh-chat-message bot">
                <div class="eh-chat-bubble">
                    Hello. I can help you navigate ElevateHer360, including accessibility support.
                </div>
            </div>
        </div>

        <form class="eh-chatbot-form" data-chat-form action="{{ url('/support/chatbot') }}" method="POST">
            @csrf
            <label for="ehChatMessage" class="sr-only">Ask ElevateHer360 Assistant</label>
            <textarea
                id="ehChatMessage"
                name="message"
                rows="2"
                maxlength="1000"
                placeholder="Ask about learning, mentorship, jobs, library, profile or accessibility..."
                required
            ></textarea>
            <button type="submit" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
        </form>
        <small class="eh-chatbot-note">Do not share passwords, API keys, bank details or other confidential credentials.</small>
    </div>

    <div class="eh-assistive-launchers" aria-label="Support tools">
        <button
            type="button"
            class="eh-assistive-trigger accessibility"
            data-eh-toggle="accessibility"
            aria-controls="ehAccessibilityPanel"
            aria-expanded="false"
            title="Accessibility options"
        >
            <i class="fas fa-universal-access"></i>
            <span class="sr-only">Accessibility options</span>
        </button>

        <button
            type="button"
            class="eh-assistive-trigger chatbot"
            data-eh-toggle="chatbot"
            aria-controls="ehChatbotPanel"
            aria-expanded="false"
            title="ElevateHer360 Assistant"
        >
            <i class="fas fa-comments"></i>
            <span class="sr-only">Open ElevateHer360 Assistant</span>
        </button>
    </div>

    <div class="eh-reading-guide" data-reading-guide hidden aria-hidden="true"></div>
</div>
