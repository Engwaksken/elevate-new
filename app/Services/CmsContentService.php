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
        'gallery' => 'Image grid',
        'video' => 'Video',
    ];

    /** Background modes every section can use. */
    public const BACKGROUNDS = [
        'none' => 'Default', 'color' => 'Solid colour', 'gradient' => 'Gradient',
        'image' => 'Image', 'video' => 'Video',
    ];

    public const SOCIAL_NETWORKS = [
        'facebook' => ['Facebook', 'fa-facebook-f'], 'x' => ['X (Twitter)', 'fa-x-twitter'],
        'linkedin' => ['LinkedIn', 'fa-linkedin-in'], 'instagram' => ['Instagram', 'fa-instagram'],
        'youtube' => ['YouTube', 'fa-youtube'], 'tiktok' => ['TikTok', 'fa-tiktok'],
        'whatsapp' => ['WhatsApp', 'fa-whatsapp'],
    ];

    /** Uploadable media: extension => mime. SVG is excluded (it can carry script). */
    public const MEDIA_TYPES = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
        'gif' => 'image/gif', 'mp4' => 'video/mp4', 'webm' => 'video/webm',
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
        // Render markdown and allow raw HTML so admins can paste rich content
        // (headings, links, lists, images), then sanitise the result with the
        // strict whitelist so scripts, event handlers and unsafe navigation
        // are stripped.
        $html = Str::markdown($body ?? '', ['html_input' => 'allow', 'allow_unsafe_links' => false]);
        return $this->sanitizeHtml($html);
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
        // In-page anchors such as #platform (section anchor IDs).
        if (preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/', $url)) return true;
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true);
        }
        return false;
    }

    /** Public URL for a file stored on the public disk under cms/ (served by a route, so no storage symlink is needed). */
    public function mediaUrl(?string $path): ?string
    {
        $path = (string) $path;
        if (! $this->isMediaPath($path)) return null;
        // Relative, so images keep working whatever scheme/host Laravel sees behind the host's proxy or SSL.
        return \Illuminate\Support\Facades\Route::has('cms.media') ? route('cms.media', ['path' => $path], false) : null;
    }

    /**
     * Clean an image/video address before it is stored: percent-encode
     * characters that break src/url() (spaces, quotes, brackets) instead of
     * rejecting the whole URL, and make links to this site's own uploads relative.
     */
    public function normalizeMediaUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') return '';
        $url = strtr($url, [' ' => '%20', '"' => '%22', "'" => '%27', '(' => '%28', ')' => '%29', '<' => '%3C', '>' => '%3E']);
        if (preg_match('#^https?://[^/]+(/cms-media/cms/[A-Za-z0-9/_.-]+)$#i', $url, $m)) {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $own = array_filter([strtolower((string) request()->getHost()), strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST))]);
            if (in_array($host, $own, true)) return $m[1];
        }
        return $url;
    }

    public function isMediaPath(string $path): bool
    {
        if ($path === '' || str_contains($path, '\\') || str_starts_with($path, '/')) return false;
        $segments = explode('/', $path);
        return count($segments) >= 2 && $segments[0] === 'cms'
            && ! array_intersect($segments, ['', '.', '..'])
            && (bool) preg_match('#^[A-Za-z0-9/_.-]+$#', $path)
            && array_key_exists(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::MEDIA_TYPES);
    }

    /** A URL that is safe to place inside CSS url("...") and HTML src attributes. */
    public function safeMediaUrl(?string $url): bool
    {
        $url = trim((string) $url);
        return $url !== '' && $this->safeUrl($url) && ! preg_match('/["\'()\\\\\s<>]/', $url);
    }

    /** Turn YouTube watch/short links and Vimeo page links into embed URLs; anything else is returned unchanged. */
    public function toEmbedUrl(?string $url): string
    {
        $url = trim((string) $url);
        if (preg_match('#^https?://(?:www\.|m\.)?youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{6,})#', $url, $m)
            || preg_match('#^https?://youtu\.be/([A-Za-z0-9_-]{6,})#', $url, $m)
            || preg_match('#^https?://(?:www\.)?youtube\.com/(?:shorts|live)/([A-Za-z0-9_-]{6,})#', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }
        if (preg_match('#^https?://(?:www\.)?vimeo\.com/([0-9]+)#', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }
        return $url;
    }

    /** A directly playable video file (uploaded or an external .mp4/.webm). */
    public function isDirectVideo(?string $url): bool
    {
        $url = trim((string) $url);
        if (! $this->safeMediaUrl($url)) return false;
        $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'webm'], true);
    }

    /** Embed URL for a background video iframe: autoplay, muted, looping, no controls. */
    public function backgroundEmbedUrl(string $embed): string
    {
        if (preg_match('#/embed/([A-Za-z0-9_-]+)$#', $embed, $m)) {
            return $embed.'?autoplay=1&mute=1&loop=1&playlist='.$m[1].'&controls=0&showinfo=0&modestbranding=1&playsinline=1&rel=0';
        }
        return $embed.'?background=1&autoplay=1&muted=1&loop=1';
    }

    /**
     * The built-in home page (resources/views/home.blade.php) as editable
     * sections, so admins start from the existing content and layout.
     */
    public static function defaultHomeSections(): array
    {
        $none = ['bg' => 'none'];
        return [
            [
                'type' => 'hero', 'design' => 'classic', 'label' => 'Main banner',
                'eyebrow' => 'Your complete growth journey',
                'title' => 'Learn, connect, build your career and move into opportunity.',
                'text' => 'ElevateHer360 brings training, mentorship, career development, employment opportunities and digital resources together under one account.',
                'primary_label' => 'Get Started', 'primary_url' => '#get-started',
                'secondary_label' => 'Explore the Platform', 'secondary_url' => '#platform',
                'image_url' => '',
                'panel_eyebrow' => 'One connected journey', 'panel_title' => 'From registration to outcomes',
                'items' => [
                    ['title' => 'Learn', 'text' => 'Courses, assessments, attendance and certificates'],
                    ['title' => 'Connect', 'text' => 'Mentors, goals, sessions and milestones'],
                    ['title' => 'Prepare', 'text' => 'Career coaching, skills and resume building'],
                    ['title' => 'Progress', 'text' => 'Job applications, employment and outcome tracking'],
                ],
                'style' => $none,
            ],
            [
                'type' => 'features', 'design' => 'classic', 'label' => 'Platform features',
                'eyebrow' => 'Everything in one place', 'title' => 'One participant. One platform.',
                'intro' => 'Your learning, mentorship, career and employment journey stays connected to one profile.',
                'items' => [
                    ['icon' => 'fa-graduation-cap', 'title' => 'Learning', 'text' => 'Explore courses, lessons, assessments, attendance, progress tracking and certificates.', 'url' => '/learning', 'link_label' => 'Explore Learning'],
                    ['icon' => 'fa-handshake', 'title' => 'Mentorship', 'text' => 'Connect with mentors, manage goals, sessions, milestones and action plans.', 'url' => '/mentorship', 'link_label' => 'Open Mentorship'],
                    ['icon' => 'fa-briefcase', 'title' => 'Jobs', 'text' => 'Discover opportunities, save jobs, apply and track your application journey.', 'url' => '/jobs', 'link_label' => 'View Jobs'],
                    ['icon' => 'fa-book-open', 'title' => 'Digital Library', 'text' => 'Access curated learning, career and professional development resources.', 'url' => '/library', 'link_label' => 'Browse Library'],
                    ['icon' => 'fa-compass', 'title' => 'Career Development', 'text' => 'Strengthen career readiness, interview skills and workplace preparation.', 'url' => '/career/resumes', 'link_label' => 'Build Your Career'],
                    ['icon' => 'fa-file-lines', 'title' => 'Resume Builder', 'text' => 'Build and manage professional resumes for job and career opportunities.', 'url' => '/career/resumes', 'link_label' => 'Build Resume'],
                    ['icon' => 'fa-calendar-days', 'title' => 'Calendar', 'text' => 'Keep training, mentorship, deadlines and interview dates organised.', 'url' => '/dashboard', 'link_label' => 'View Calendar'],
                    ['icon' => 'fa-chart-line', 'title' => 'Your Progress', 'text' => 'Follow achievements across learning, mentorship, jobs and employment outcomes.', 'url' => '/dashboard', 'link_label' => 'View Progress'],
                ],
                'style' => $none + ['anchor' => 'platform'],
            ],
            [
                'type' => 'stats', 'design' => 'classic', 'label' => 'Impact',
                'eyebrow' => 'Designed for measurable impact', 'title' => 'Connecting programme delivery to participant outcomes.',
                'intro' => 'ElevateHer360 provides a connected view from participant registration and training through mentorship, career development, employment and self-employment outcomes.',
                'items' => [
                    ['value' => 'One', 'label' => 'Identity', 'icon' => 'fa-user', 'text' => 'One participant profile across the platform.'],
                    ['value' => '360', 'label' => 'Journey', 'icon' => 'fa-arrows-rotate', 'text' => 'Connected learning-to-opportunity journey.'],
                    ['value' => 'RBAC', 'label' => 'Access', 'icon' => 'fa-shield-halved', 'text' => 'Secure role-based access control.'],
                    ['value' => 'M&E', 'label' => 'Results', 'icon' => 'fa-chart-column', 'text' => 'Outcome tracking.'],
                ],
                'style' => $none + ['anchor' => 'impact'],
            ],
            [
                'type' => 'cta', 'design' => 'classic', 'label' => 'Closing call to action',
                'eyebrow' => 'Take the next step', 'title' => 'Ready to begin your ElevateHer360 journey?',
                'content' => 'Create one participant account for learning, mentorship, careers, jobs and digital resources.',
                'button_label' => 'Create Account', 'button_url' => '/register',
                'secondary_label' => 'Sign In', 'secondary_url' => '/login',
                'style' => $none,
            ],
        ];
    }

    /** Return $raw[$key] when it is one of $allowed, otherwise $default. */
    public static function pick(array $raw, string $key, array $allowed, string $default): string
    {
        $value = $raw[$key] ?? $default;
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }

    public function hexColor($value): string
    {
        $value = trim((string) $value);
        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) ? strtolower($value) : '';
    }

    /** Normalise the "Background & layout" options posted for a section. */
    public function parseStyle($raw, string $legacyBackground = ''): array
    {
        $raw = is_array($raw) ? $raw : [];
        $bg = (string) ($raw['bg'] ?? 'none');
        if (! array_key_exists($bg, self::BACKGROUNDS)) $bg = 'none';
        $image = $this->normalizeMediaUrl($raw['bg_image'] ?? '');
        $video = $this->toEmbedUrl($this->normalizeMediaUrl($raw['bg_video'] ?? ''));
        $style = [
            'bg' => $bg,
            'bg_color' => $this->hexColor($raw['bg_color'] ?? ''),
            'bg_color2' => $this->hexColor($raw['bg_color2'] ?? ''),
            'gradient_angle' => max(0, min(360, (int) ($raw['gradient_angle'] ?? 135))),
            'bg_image' => $this->safeMediaUrl($image) ? $image : '',
            'bg_size' => self::pick($raw, 'bg_size', ['cover', 'contain', 'repeat'], 'cover'),
            'bg_position' => self::pick($raw, 'bg_position', ['center', 'top', 'bottom', 'left', 'right'], 'center'),
            'parallax' => ! empty($raw['parallax']),
            'bg_video' => ($this->isDirectVideo($video) || $this->isValidVideoEmbed($video)) ? $video : '',
            'overlay_color' => $this->hexColor($raw['overlay_color'] ?? ''),
            'overlay_opacity' => max(0, min(90, (int) ($raw['overlay_opacity'] ?? 0))),
            'text_tone' => self::pick($raw, 'text_tone', ['auto', 'light', 'dark'], 'auto'),
            'padding' => self::pick($raw, 'padding', ['none', 'sm', 'md', 'lg', 'xl'], 'md'),
            'align' => self::pick($raw, 'align', ['left', 'center'], 'left'),
            'full_width' => ! empty($raw['full_width']),
            'min_height' => max(0, min(100, (int) ($raw['min_height'] ?? 0))),
            'anchor' => preg_match('/^[a-z][a-z0-9-]{0,40}$/', (string) ($raw['anchor'] ?? '')) ? $raw['anchor'] : '',
        ];
        // Older "Section / Grid" blocks stored a single background field (hex or "soft").
        if ($bg === 'none' && $legacyBackground !== '') {
            if ($legacyBackground === 'soft') $style['bg_color'] = '#fffaf5';
            elseif ($hex = $this->hexColor($legacyBackground)) $style['bg_color'] = $hex;
            if ($style['bg_color'] !== '') $style['bg'] = 'color';
        }
        return $style;
    }

    /**
     * Presentation for a section's outer element: extra classes, inline style,
     * and the optional video/overlay layers. Inputs are re-validated here so
     * stored data that bypassed the editor still cannot inject CSS or markup.
     */
    public function appearance(array $section): array
    {
        $s = is_array($section['style'] ?? null) ? $section['style'] : [];
        if (($section['type'] ?? '') === 'section' && empty($s) && ! empty($section['background'])) {
            $s = $this->parseStyle([], (string) $section['background']);
            $s['full_width'] = ! empty($section['full_width']);
        }
        $bg = (string) ($s['bg'] ?? 'none');
        $css = [];
        $classes = [];
        $video = null;
        $iframe = null;
        $color = $this->hexColor($s['bg_color'] ?? '');
        if ($bg === 'color' && $color) {
            $css[] = 'background:'.$color;
        } elseif ($bg === 'gradient' && $color) {
            $color2 = $this->hexColor($s['bg_color2'] ?? '') ?: $color;
            $css[] = 'background:linear-gradient('.((int) ($s['gradient_angle'] ?? 135)).'deg,'.$color.','.$color2.')';
        } elseif ($bg === 'image' && $this->safeMediaUrl($s['bg_image'] ?? '')) {
            $size = $s['bg_size'] ?? 'cover';
            $pos = in_array($s['bg_position'] ?? 'center', ['center', 'top', 'bottom', 'left', 'right'], true) ? $s['bg_position'] : 'center';
            $css[] = 'background-color:'.($color ?: '#222');
            $css[] = 'background-image:url("'.$s['bg_image'].'")';
            $css[] = 'background-position:'.$pos;
            $css[] = $size === 'repeat' ? 'background-repeat:repeat' : 'background-repeat:no-repeat;background-size:'.($size === 'contain' ? 'contain' : 'cover');
            if (! empty($s['parallax'])) $classes[] = 'cms-bg-parallax';
        } elseif ($bg === 'video') {
            $url = (string) ($s['bg_video'] ?? '');
            if ($this->isDirectVideo($url)) $video = $url;
            elseif ($this->isValidVideoEmbed($url)) $iframe = $this->backgroundEmbedUrl($url);
            if ($video || $iframe) $css[] = 'background:'.($color ?: '#111');
        }
        $hasMedia = $video || $iframe || ($bg === 'image' && $css);
        $tone = $s['text_tone'] ?? 'auto';
        if ($tone === 'auto' && $hasMedia) $tone = 'light';
        if (in_array($tone, ['light', 'dark'], true)) $classes[] = 'cms-tone-'.$tone;
        if ($css || $video || $iframe) $classes[] = 'cms-has-bg';
        $padding = $s['padding'] ?? 'md';
        if (in_array($padding, ['none', 'sm', 'lg', 'xl'], true)) $classes[] = 'cms-pad-'.$padding;
        if (($s['align'] ?? 'left') === 'center') $classes[] = 'cms-align-center';
        if (($h = (int) ($s['min_height'] ?? 0)) > 0) {
            $css[] = 'min-height:'.min(100, $h).'vh';
            $classes[] = 'cms-min-height';
        }
        $overlayOpacity = (int) ($s['overlay_opacity'] ?? 0);
        $overlay = $overlayOpacity > 0
            ? 'background:'.($this->hexColor($s['overlay_color'] ?? '') ?: '#000000').';opacity:'.round(min(90, $overlayOpacity) / 100, 2)
            : null;
        if ($overlay) $classes[] = 'cms-has-bg';

        return [
            'class' => implode(' ', array_unique($classes)),
            'style' => implode(';', $css),
            'video' => $video,
            'iframe' => $iframe,
            'overlay' => $overlay,
            'full_width' => ! empty($s['full_width']),
            'anchor' => preg_match('/^[a-z][a-z0-9-]{0,40}$/', (string) ($s['anchor'] ?? '')) ? $s['anchor'] : null,
        ];
    }

    /** Published header/footer settings merged over defaults, for the public layout. */
    public function siteChrome(): array
    {
        $header = $this->published('site-header');
        $footer = $this->published('site-footer');
        $h = (array) data_get($header, 'settings.header', []);
        $a = (array) data_get($header, 'settings.announcement', []);
        $f = (array) data_get($footer, 'settings.footer', []);
        $media = fn ($content) => ($p = $content['image_path'] ?? null) ? $this->mediaUrl($p) : null;
        return [
            'header' => $header,
            'footer' => $footer,
            'site_name' => trim((string) ($header['title'] ?? '')) ?: 'ElevateHer360',
            'tagline' => $header ? (string) ($header['summary'] ?? '') : 'Women in Technology Uganda',
            'header_logo' => $media($header),
            'footer_logo' => $media($footer),
            'h' => $h + ['logo_height' => 48, 'show_name' => true, 'show_tagline' => true, 'sticky' => true, 'show_partners' => true, 'show_auth' => true],
            'announcement' => $a,
            'f' => $f + ['show_logo' => true],
        ];
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
