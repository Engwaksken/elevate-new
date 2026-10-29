<?php

namespace Tests\Feature;

use App\Support\AiPlainText;
use Tests\TestCase;

class AiPlainTextTest extends TestCase
{
    public function test_markdown_and_typographic_dashes_are_removed(): void
    {
        $raw = "Your goal is **Get IT internship**.\n\n1. **Match to your skills** – Your diploma helps.\n\n**Next steps (choose 2–3):**\n\n- Pick one sub‑field — then build a small project.\n* Push the code to `GitHub`.\n\n### Summary\nGood luck—you're halfway there!";

        $clean = AiPlainText::clean($raw);

        $this->assertStringNotContainsString('*', $clean);
        $this->assertStringNotContainsString('–', $clean);
        $this->assertStringNotContainsString('—', $clean);
        $this->assertStringNotContainsString("\u{2011}", $clean);
        $this->assertStringNotContainsString('#', $clean);
        $this->assertStringNotContainsString('`', $clean);
        $this->assertStringContainsString('Your goal is Get IT internship.', $clean);
        $this->assertStringContainsString('1. Match to your skills. Your diploma helps.', $clean);
        $this->assertStringContainsString('Next steps (choose 2 to 3):', $clean);
        $this->assertStringContainsString('• Pick one sub-field, then build a small project.', $clean);
        $this->assertStringContainsString('• Push the code to GitHub.', $clean);
        $this->assertStringContainsString("Summary\nGood luck, you're halfway there!", $clean);
    }

    public function test_plain_text_is_left_unchanged(): void
    {
        $text = "Hi Kenneth, start by listing three roles you enjoy.\n\n1. Read two job adverts.\n2. Update your CV.";

        $this->assertSame($text, AiPlainText::clean($text));
    }
}
