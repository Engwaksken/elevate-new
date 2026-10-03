<?php

namespace App\Services;

use App\Models\CmsPage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CmsContentService
{
    public const BLOCKS = [
        'hero' => 'Hero banner',
        'text' => 'Text section',
        'image_text' => 'Image & text',
        'features' => 'Feature cards',
        'stats' => 'Statistics',
        'cta' => 'Call to action',
        'section' => 'Section / Grid',
    ];

    public const WIDGETS = [
        'heading' => 'Heading',
        'text' => 'Text',
        'image' => 'Image',
        'button' => 'Button',
        'spacer' => 'Spacer',
        'divider' => 'Divider',
        'video' => 'Video embed',
        'html' => 'Custom HTML',
    ];

    public const COLUMN_WIDTHS = ['1/1', '1/2', '1/3', '2/3', '1/4', '3/4', '1/6', '5/6'];

    /**
     * Conservative tag => allowed-attributes whitelist for the `html` widget.
     * Anything not listed is stripped (script/style/noscript/template/object/embed/applet
     * are dropped together with their content; other unknown tags are unwrapped).
     */
    private const HTML_ALLOWLIST = [
        'p' => [], 'br' => [], 'hr' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [],
        'u' => [], 's' => [], 'del' => [], 'ins' => [], 'mark' => [],
        'small' => [], 'sub' => [], 'sup' => [],
        'blockquote' => [], 'code' => [], 'pre' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'span' => [], 'div' => [],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'iframe' => ['src', 'title', 'width', 'height', 'allowfullscreen'],
    ];

    public const PAGES = [
        'home' => ['Home', 'home'], 'site-header' => ['ElevateHer360', null], 'site-footer' => ['ElevateHer360', null],
        'privacy-policy' => ['Privacy Policy', 'legal.privacy'], 'terms' => ['Terms of Use', 'legal.terms'],
        'learning' => ['Courses', 'learning.index'], 'jobs' => ['Jobs', 'jobs.index'],
        'library' => ['Digital Library', 'library.index'], 'events' => ['Upcoming Events', 'events.index'],
        'faqs' => ['Frequently Asked Questions', 'public.faqs'], 'mentor-signup' => ['Become a Mentor', 'public.partners.mentor'],
        'employer-signup' => ['Register an Employer', 'public.partners.employer'],
        'register' => ['Participant Registration', 'register'], 'login' => ['Participant Sign In', 'login'],
    ];

    public function published(string $slug): ?array
    {
        // Public pages must still render during deployments before CMS migrations run.
        $attributes = request()->attributes;
        if (! $attributes->has('cms.available')) {
            $attributes->set('cms.available', Schema::hasTable('cms_pages'));
        }
        if (! $attributes->get('cms.available')) return null;
        $key = 'cms.published.'.$slug;
        if (! $attributes->has($key)) {
            $attributes->set($key, CmsPage::where('slug', $slug)->value('published_data'));
        }
        return $attributes->get($key);
    }

    public function text(string $slug, string $field, string $fallback = ''): string
    {
        return (string) data_get($this->published($slug), $field, $fallback);
    }

    public function render(?string $body): string
    {
        return Str::markdown($body ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    public function publicUrl(CmsPage $page): ?string
    {
        if (in_array($page->slug, ['site-header', 'site-footer'], true)) return null;
        $route = self::PAGES[$page->slug][1] ?? null;
        return $route ? route($route) : route('public.pages.show', $page->slug);
    }

    public function sections(array $content): array
    {
        $sections = data_get($content, 'settings.sections', []);
        return is_array($sections) ? array_values(array_filter($sections, fn ($s) => is_array($s) && ! empty($s['type']))) : [];
    }

    public function safeUrl(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') return true;
        if (preg_match('#^/(?!/)[A-Za-z0-9/_.?=&%\#-]*$#', $url)) return true;
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true);
        }
        return false;
    }

    /**
     * Validate a video `embed_url` as an HTTPS YouTube or Vimeo embed only.
     * Accepts youtube.com/embed, youtube-nocookie.com/embed and player.vimeo.com/video.
     */
    public function isValidVideoEmbed(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') return false;
        $parts = parse_url($url);
        if (! is_array($parts)) return false;
        if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https') return false;
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            return (bool) preg_match('#^/embed/[A-Za-z0-9_-]+$#', $path);
        }
        if (in_array($host, ['player.vimeo.com', 'www.player.vimeo.com'], true)) {
            return (bool) preg_match('#^/video/[0-9]+$#', $path);
        }
        return false;
    }

    /**
     * Sanitize raw HTML for the `html` widget using a strict whitelist.
     * Removes <script>/<style>/<noscript>/<template>/<object>/<embed>/<applet>,
     * every on* event attribute, inline style attributes, javascript:/vbscript:/data:/file:
     * URIs, and any non-HTTPS iframe. Unknown tags are unwrapped (children kept).
     */
    public function sanitizeHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') return '';

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) return strip_tags($html);

        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body) return '';

        $this->sanitizeNodes($body);

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        return trim($out);
    }

    private function sanitizeNodes(\DOMNode $node): void
    {
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $this->sanitizeElement($child);
            } elseif ($child->nodeType !== XML_TEXT_NODE) {
                $node->removeChild($child);
            }
        }
    }

    private function sanitizeElement(\DOMElement $element): void
    {
        $tag = strtolower($element->nodeName);

        if (in_array($tag, ['script', 'style', 'noscript', 'template', 'object', 'embed', 'applet'], true)) {
            if ($element->parentNode) $element->parentNode->removeChild($element);
            return;
        }

        if (! isset(self::HTML_ALLOWLIST[$tag])) {
            $this->unwrapElement($element);
            return;
        }

        $this->sanitizeAttributes($element, self::HTML_ALLOWLIST[$tag]);

        if ($tag === 'iframe' && ! $this->isHttpsUri($element->getAttribute('src'))) {
            if ($element->parentNode) $element->parentNode->removeChild($element);
            return;
        }

        $this->sanitizeNodes($element);
    }

    private function unwrapElement(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) return;
        $this->sanitizeNodes($element);
        while ($element->childNodes->length > 0) {
            $parent->insertBefore($element->childNodes->item(0), $element);
        }
        $parent->removeChild($element);
    }

    private function sanitizeAttributes(\DOMElement $element, array $allowed): void
    {
        $names = [];
        foreach ($element->attributes as $attr) {
            $names[] = $attr->name;
        }
        foreach ($names as $name) {
            $lower = strtolower($name);
            $value = trim($element->getAttribute($name));

            if (str_starts_with($lower, 'on') || $lower === 'style') {
                $element->removeAttribute($name);
                continue;
            }
            if (! in_array($lower, $allowed, true)) {
                $element->removeAttribute($name);
                continue;
            }
            if ($lower === 'href' || $lower === 'src') {
                if (! $this->isSafeUri($value)) $element->removeAttribute($name);
            } elseif ($lower === 'target' && ! in_array(strtolower($value), ['_blank', '_self'], true)) {
                $element->removeAttribute($name);
            }
        }
    }

    private function isSafeUri(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || $value[0] === '#') return true;
        if (preg_match('#^(javascript|vbscript|data|file):#i', $value)) return false;
        if (str_starts_with($value, '//')) return true;
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if ($scheme === '') return true;
        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }

    private function isHttpsUri(string $value): bool
    {
        $value = trim($value);
        if ($value === '') return false;
        return strtolower((string) parse_url($value, PHP_URL_SCHEME)) === 'https';
    }
}
