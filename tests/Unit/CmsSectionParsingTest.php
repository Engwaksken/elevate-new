<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\CmsPageController;
use ReflectionMethod;
use Tests\TestCase;

class CmsSectionParsingTest extends TestCase
{
    private function parseSections(array $input): array
    {
        $method = new ReflectionMethod(CmsPageController::class, 'parseSections');
        $method->setAccessible(true);

        return $method->invoke(app(CmsPageController::class), $input);
    }

    public function test_section_block_round_trips_all_widget_types(): void
    {
        $result = $this->parseSections([[
            'type' => 'section',
            'background' => '#F4F4F4',
            'full_width' => 1,
            'columns' => [[
                'width' => '1/2',
                'widgets' => [
                    ['type' => 'heading', 'level' => 'h2', 'text' => 'Hello <b>World</b>'],
                    ['type' => 'text', 'content' => 'Some **markdown** <i>text</i>'],
                    ['type' => 'image', 'image_url' => '/images/x.png', 'alt' => 'An image'],
                    ['type' => 'button', 'label' => 'Go', 'url' => 'https://example.com', 'style' => 'outline'],
                    ['type' => 'spacer', 'height' => 24],
                    ['type' => 'divider'],
                    ['type' => 'video', 'embed_url' => 'https://www.youtube.com/embed/abc123DEF_-'],
                    ['type' => 'html', 'content' => '<p>Hi <strong>there</strong></p>'],
                ],
            ]],
        ]]);

        $this->assertSame([[
            'type' => 'section',
            'background' => '#f4f4f4',
            'full_width' => true,
            'columns' => [[
                'width' => '1/2',
                'widgets' => [
                    ['type' => 'heading', 'level' => 'h2', 'text' => 'Hello World'],
                    ['type' => 'text', 'content' => 'Some **markdown** text'],
                    ['type' => 'image', 'image_url' => '/images/x.png', 'alt' => 'An image'],
                    ['type' => 'button', 'label' => 'Go', 'url' => 'https://example.com', 'style' => 'outline'],
                    ['type' => 'spacer', 'height' => 24],
                    ['type' => 'divider'],
                    ['type' => 'video', 'embed_url' => 'https://www.youtube.com/embed/abc123DEF_-'],
                    ['type' => 'html', 'content' => '<p>Hi <strong>there</strong></p>'],
                ],
            ]],
        ]], $result);
    }

    public function test_section_defaults_and_soft_background(): void
    {
        $result = $this->parseSections([[
            'type' => 'section',
            'columns' => [[
                'widgets' => [['type' => 'divider']],
            ]],
        ]]);

        $this->assertSame('', $result[0]['background']);
        $this->assertSame(false, $result[0]['full_width']);
        $this->assertSame('1/1', $result[0]['columns'][0]['width']);

        $soft = $this->parseSections([[
            'type' => 'section',
            'background' => 'soft',
            'columns' => [['widgets' => [['type' => 'divider']]]],
        ]]);
        $this->assertSame('soft', $soft[0]['background']);
    }

    public function test_section_limits_are_enforced(): void
    {
        // 60 sections -> capped at 50
        $sections = [];
        for ($i = 0; $i < 60; $i++) {
            $sections[] = ['type' => 'section', 'columns' => [['widgets' => [['type' => 'divider']]]]];
        }
        $this->assertCount(50, $this->parseSections($sections));

        // 8 columns -> capped at 6
        $columns = [];
        for ($i = 0; $i < 8; $i++) {
            $columns[] = ['width' => '1/1', 'widgets' => [['type' => 'divider']]];
        }
        $result = $this->parseSections([['type' => 'section', 'columns' => $columns]]);
        $this->assertCount(6, $result[0]['columns']);

        // 25 widgets -> capped at 20
        $widgets = [];
        for ($i = 0; $i < 25; $i++) {
            $widgets[] = ['type' => 'spacer', 'height' => $i];
        }
        $result = $this->parseSections([['type' => 'section', 'columns' => [['widgets' => $widgets]]]]);
        $this->assertCount(20, $result[0]['columns'][0]['widgets']);
    }

    public function test_unsafe_html_widget_is_sanitized(): void
    {
        $raw = '<script>alert(1)</script>'
            .'<style>body{color:red}</style>'
            .'<p onclick="alert(2)" style="color:red">Hi</p>'
            .'<img src="x.png" onerror="alert(3)">'
            .'<a href="javascript:alert(4)">link</a>'
            .'<a href="vbscript:msgbox(1)">vb</a>'
            .'<iframe src="http://example.com/embed/x"></iframe>'
            .'<iframe src="https://www.youtube.com/embed/abc"></iframe>'
            .'<strong>bold</strong>';

        $result = $this->parseSections([[
            'type' => 'section',
            'columns' => [['widgets' => [['type' => 'html', 'content' => $raw]]]],
        ]]);

        $content = $result[0]['columns'][0]['widgets'][0]['content'];

        $this->assertStringNotContainsString('script', $content);
        $this->assertStringNotContainsString('<style', $content);
        $this->assertStringNotContainsString('onclick', $content);
        $this->assertStringNotContainsString('onerror', $content);
        $this->assertStringNotContainsString('javascript:', $content);
        $this->assertStringNotContainsString('vbscript:', $content);
        $this->assertStringNotContainsString('http://', $content);
        $this->assertStringContainsString('https://www.youtube.com/embed/abc', $content);
        $this->assertStringContainsString('<strong>bold</strong>', $content);
        $this->assertStringContainsString('Hi', $content);
    }

    public function test_bad_video_embed_url_is_rejected(): void
    {
        $bad = [
            'https://www.youtube.com/watch?v=abc', // watch URL, not embed
            'http://www.youtube.com/embed/abc',    // non-HTTPS
            'https://youtu.be/abc',                // shortlink
            'https://player.vimeo.com/123',        // missing /video/
            'https://evil.com/embed/abc',          // wrong host
            'javascript:alert(1)',
        ];
        foreach ($bad as $url) {
            $result = $this->parseSections([[
                'type' => 'section',
                'columns' => [['widgets' => [['type' => 'video', 'embed_url' => $url]]]],
            ]]);
            $this->assertSame([], $result, "expected video embed rejected: $url");
        }

        $good = [
            'https://www.youtube.com/embed/abc',
            'https://youtube-nocookie.com/embed/abc',
            'https://player.vimeo.com/video/123456',
        ];
        foreach ($good as $url) {
            $result = $this->parseSections([[
                'type' => 'section',
                'columns' => [['widgets' => [['type' => 'video', 'embed_url' => $url]]]],
            ]]);
            $this->assertSame($url, $result[0]['columns'][0]['widgets'][0]['embed_url']);
        }
    }

    public function test_bad_column_width_defaults_to_full(): void
    {
        $result = $this->parseSections([[
            'type' => 'section',
            'columns' => [['width' => '2/5', 'widgets' => [['type' => 'divider']]]],
        ]]);
        $this->assertSame('1/1', $result[0]['columns'][0]['width']);
    }

    public function test_unknown_widget_and_empty_widgets_are_dropped(): void
    {
        $result = $this->parseSections([[
            'type' => 'section',
            'columns' => [[
                'widgets' => [
                    ['type' => 'widget-that-does-not-exist', 'foo' => 'bar'],
                    ['type' => 'heading', 'text' => ''],   // empty -> dropped
                    ['type' => 'divider'],                  // kept
                ],
            ]],
        ]]);

        $this->assertSame([['type' => 'divider']], $result[0]['columns'][0]['widgets']);
    }

    public function test_existing_flat_blocks_round_trip_unchanged(): void
    {
        $result = $this->parseSections([
            ['type' => 'hero', 'eyebrow' => 'Hi', 'title' => 'Title', 'text' => 'Body',
                'primary_label' => 'Go', 'primary_url' => '/go',
                'secondary_label' => 'More', 'secondary_url' => 'https://example.com',
                'image_url' => '/img.png'],
            ['type' => 'text', 'title' => 'T', 'content' => 'C'],
            ['type' => 'image_text', 'title' => 'T', 'content' => 'C', 'image_url' => '/i.png', 'reverse' => 'on'],
            ['type' => 'cta', 'title' => 'T', 'content' => 'C', 'button_label' => 'Go', 'button_url' => '/go'],
            ['type' => 'features', 'title' => 'T', 'intro' => 'I',
                'items' => [['icon' => 'star', 'title' => 'Item', 'text' => 'Desc']]],
            ['type' => 'stats', 'title' => 'T', 'items' => [['value' => '12', 'label' => 'Years']]],
        ]);

        $this->assertSame([
            ['type' => 'hero', 'eyebrow' => 'Hi', 'title' => 'Title', 'text' => 'Body',
                'primary_label' => 'Go', 'primary_url' => '/go',
                'secondary_label' => 'More', 'secondary_url' => 'https://example.com',
                'image_url' => '/img.png'],
            ['type' => 'text', 'title' => 'T', 'content' => 'C'],
            ['type' => 'image_text', 'title' => 'T', 'content' => 'C', 'image_url' => '/i.png', 'reverse' => true],
            ['type' => 'cta', 'title' => 'T', 'content' => 'C', 'button_label' => 'Go', 'button_url' => '/go'],
            ['type' => 'features', 'title' => 'T', 'intro' => 'I',
                'items' => [['icon' => 'star', 'title' => 'Item', 'text' => 'Desc']]],
            ['type' => 'stats', 'title' => 'T', 'items' => [['value' => '12', 'label' => 'Years']]],
        ], $result);
    }
}
