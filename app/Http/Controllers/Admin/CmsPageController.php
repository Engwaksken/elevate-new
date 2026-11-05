<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\CmsContentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CmsPageController extends Controller
{
    public function index()
    {
        return view('admin.cms.index', ['pages' => CmsPage::orderBy('slug')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['slug' => ['required', 'regex:/^[a-z][a-z0-9-]*$/', 'max:100', 'unique:cms_pages,slug'], 'title' => ['required', 'string', 'max:190']]);
        $page = CmsPage::create($data + ['updated_by' => $request->user()->id]);
        return redirect()->route('admin.cms.edit', $page)->with('success', 'Draft page created.');
    }

    public function edit(CmsPage $page)
    {
        $view = ['site-header' => 'admin.cms.header', 'site-footer' => 'admin.cms.footer', 'faqs' => 'admin.cms.faqs'][$page->slug] ?? 'admin.cms.edit';
        return view($view, compact('page'));
    }

    public function update(Request $request, CmsPage $page)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'], 'summary' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string', 'max:200000'], 'action' => ['required', 'in:save,publish,unpublish'],
            'links_text' => ['nullable', 'string', 'max:10000'], 'field_labels_text' => ['nullable', 'string', 'max:10000'],
            'button_label' => ['nullable', 'string', 'max:100'], 'signup_open' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'remove_image' => ['nullable', 'boolean'],
            'sections' => ['nullable', 'array', 'max:50'],
            'links' => ['nullable', 'array', 'max:30'], 'header' => ['nullable', 'array'], 'announcement' => ['nullable', 'array'],
            'footer' => ['nullable', 'array'], 'faqs' => ['nullable', 'array', 'max:100'],
        ]);
        $sections = $this->parseSections($request->input('sections', []));
        $links = is_array($request->input('links'))
            ? $this->parseLinkRows($request->input('links'))
            : $this->parseLinksText($data['links_text'] ?? '');
        $labels = [];
        $allowed = ['name','email','phone','country','organisation','job_title','industry','years_experience','linkedin_url','skills','languages','mentoring_areas','professional_bio','company_name','company_type','website','contact_person','location','description'];
        foreach (preg_split('/\r?\n/', trim($data['field_labels_text'] ?? '')) as $line) {
            if (trim($line) === '') continue;
            [$key, $label] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            if (! in_array($key, $allowed, true) || $label === '' || strlen($label) > 190) {
                throw ValidationException::withMessages(['field_labels_text' => 'Enter a supported field name and label, separated by |.']);
            }
            $labels[$key] = $label;
        }
        $faqs = $page->slug === 'faqs' && $request->has('faqs_submitted') ? $this->parseFaqs((array) $request->input('faqs', [])) : null;
        if ($data['action'] === 'publish' && ! in_array($page->slug, ['site-header','site-footer','learning','jobs','library','events','mentor-signup','employer-signup','register','login'], true) && blank($data['body'] ?? null) && empty($sections) && empty($faqs)) {
            throw ValidationException::withMessages(['body' => 'Add page content before publishing.']);
        }
        $attributes = collect($data)->only(['title', 'summary', 'body'])->all();
        $attributes['settings'] = ['links' => $links, 'field_labels' => $labels, 'sections' => $sections, 'button_label' => $data['button_label'] ?? null, 'signup_open' => $request->boolean('signup_open')];
        if ($page->slug === 'faqs') {
            $attributes['settings']['faqs'] = $faqs ?? (array) ($page->settings['faqs'] ?? []);
        }
        if ($page->slug === 'site-header') {
            $attributes['settings']['header'] = $this->parseHeader((array) $request->input('header', []), (array) ($page->settings['header'] ?? []), $request->has('header'));
            $attributes['settings']['announcement'] = $request->has('announcement')
                ? $this->parseAnnouncement((array) $request->input('announcement', []))
                : (array) ($page->settings['announcement'] ?? []);
        } elseif ($page->slug === 'site-footer') {
            $attributes['settings']['footer'] = $this->parseFooter((array) $request->input('footer', []), (array) ($page->settings['footer'] ?? []), $request->has('footer'));
        }
        $attributes['updated_by'] = $request->user()->id;
        if ($request->hasFile('image')) $attributes['image_path'] = $request->file('image')->store('cms', 'public');
        elseif ($request->boolean('remove_image')) $attributes['image_path'] = null;
        $page->fill($attributes);
        if ($data['action'] === 'publish') {
            $page->published_data = $page->draft();
            $page->published_at = now();
        } elseif ($data['action'] === 'unpublish') {
            $page->published_data = null;
            $page->published_at = null;
        }
        $page->save();
        return back()->with('success', $data['action'] === 'save' ? 'Draft saved; published content is unchanged.' : ($data['action'] === 'publish' ? 'Page published.' : 'Page unpublished.'));
    }

    public function preview(CmsPage $page)
    {
        $cms = app(CmsContentService::class);
        $draft = $page->draft();
        if ($page->slug === 'faqs') {
            return view('cms.faqs', ['content' => $draft, 'faqs' => (array) data_get($draft, 'settings.faqs', []), 'preview' => true]);
        }
        if (array_key_exists($page->slug, CmsContentService::LEGAL)) {
            $doc = $cms->legalDocument($page->slug);
            [$html, $toc] = $cms->withHeadingAnchors(filled($draft['body'] ?? null) ? $cms->render($draft['body']) : $cms->legalDefaultHtml($page->slug));
            $doc = ['html' => $html, 'toc' => $toc, 'title' => $draft['title'] ?: $doc['title'], 'intro' => ($draft['summary'] ?? null) ?: $doc['intro'], 'updated' => 'Draft preview'] + $doc;
            return view('legal.document', ['doc' => $doc, 'preview' => true]);
        }
        return view('cms.page', ['content' => $draft, 'preview' => true]);
    }

    public function destroy(CmsPage $page)
    {
        abort_if(array_key_exists($page->slug, CmsContentService::PAGES), 422, 'Core pages cannot be deleted.');
        $page->delete();
        return redirect()->route('admin.cms.index')->with('success', 'Page deleted.');
    }

    private function parseSections(array $input): array
    {
        $cms = app(CmsContentService::class);
        $sections = [];
        foreach (array_values($input) as $raw) {
            if (! is_array($raw)) continue;
            $type = (string) ($raw['type'] ?? '');
            if (! array_key_exists($type, CmsContentService::BLOCKS)) continue;
            $text = fn ($key, $max = 10000) => mb_substr(strip_tags((string) ($raw[$key] ?? '')), 0, $max);
            $short = fn ($key, $max = 190) => mb_substr(strip_tags((string) ($raw[$key] ?? '')), 0, $max);
            $url = function (string $key) use ($raw, $cms) {
                $value = trim((string) ($raw[$key] ?? ''));
                return $cms->safeUrl($value) ? $value : '';
            };
            $mediaField = function (string $key) use ($raw, $cms) {
                $value = $cms->normalizeMediaUrl($raw[$key] ?? '');
                return $cms->safeMediaUrl($value) ? $value : '';
            };
            $section = ['type' => $type];
            // Optional fields are only stored when used, so older blocks keep their exact shape.
            $extra = function (array $fields) use (&$section) {
                foreach ($fields as $key => $value) {
                    if ($value !== '' && $value !== [] && $value !== null) $section[$key] = $value;
                }
            };
            $design = CmsContentService::pick($raw, 'design', ['default', 'classic'], 'default');
            $safeItemUrls = function (array $items) use ($cms) {
                return array_map(function ($item) use ($cms) {
                    if (isset($item['url']) && ! $cms->safeUrl($item['url'])) $item['url'] = '';
                    return array_filter($item, fn ($v, $k) => ! in_array($k, ['url', 'link_label'], true) || $v !== '', ARRAY_FILTER_USE_BOTH);
                }, $items);
            };
            if ($type === 'hero') {
                $section += [
                    'eyebrow' => $short('eyebrow', 100), 'title' => $short('title'), 'text' => $text('text', 2000),
                    'primary_label' => $short('primary_label', 100), 'primary_url' => $url('primary_url'),
                    'secondary_label' => $short('secondary_label', 100), 'secondary_url' => $url('secondary_url'),
                    'image_url' => $mediaField('image_url'),
                ];
                $extra([
                    'design' => $design === 'classic' ? 'classic' : '',
                    'panel_eyebrow' => $short('panel_eyebrow', 100), 'panel_title' => $short('panel_title'),
                    'items' => $this->parseItems($raw['items'] ?? [], ['title' => 100, 'text' => 300], 8),
                ]);
            } elseif ($type === 'text') {
                $section += ['title' => $short('title'), 'content' => $text('content')];
            } elseif ($type === 'image_text') {
                $section += ['title' => $short('title'), 'content' => $text('content'), 'image_url' => $mediaField('image_url'), 'reverse' => ! empty($raw['reverse'])];
            } elseif ($type === 'cta') {
                $section += ['title' => $short('title'), 'content' => $text('content', 2000), 'button_label' => $short('button_label', 100), 'button_url' => $url('button_url')];
                $extra([
                    'design' => $design === 'classic' ? 'classic' : '', 'eyebrow' => $short('eyebrow', 100),
                    'secondary_label' => $short('secondary_label', 100), 'secondary_url' => $url('secondary_url'),
                ]);
            } elseif ($type === 'features') {
                $section['title'] = $short('title');
                $section['intro'] = $text('intro', 2000);
                $section['items'] = $safeItemUrls($this->parseItems($raw['items'] ?? [], ['icon' => 80, 'title' => 190, 'text' => 1000, 'url' => 500, 'link_label' => 100], 12));
                $extra(['design' => $design === 'classic' ? 'classic' : '', 'eyebrow' => $short('eyebrow', 100)]);
            } elseif ($type === 'stats') {
                $section['title'] = $short('title');
                $section['items'] = array_map(fn ($i) => array_filter($i, fn ($v, $k) => in_array($k, ['value', 'label'], true) || $v !== '', ARRAY_FILTER_USE_BOTH),
                    $this->parseItems($raw['items'] ?? [], ['value' => 80, 'label' => 190, 'icon' => 80, 'text' => 300], 8));
                $extra(['design' => $design === 'classic' ? 'classic' : '', 'eyebrow' => $short('eyebrow', 100), 'intro' => $text('intro', 2000)]);
            } elseif ($type === 'section') {
                $columns = $this->parseColumns($raw['columns'] ?? []);
                if ($columns === []) continue;
                $section['columns'] = $columns;
            } elseif ($type === 'gallery') {
                $section['title'] = $short('title');
                $section['intro'] = $text('intro', 2000);
                $section['columns'] = max(2, min(6, (int) ($raw['columns'] ?? 3)));
                $section['gap'] = CmsContentService::pick($raw, 'gap', ['none', 'sm', 'md', 'lg'], 'md');
                $section['ratio'] = CmsContentService::pick($raw, 'ratio', ['square', 'landscape', 'portrait', 'auto'], 'square');
                $section['captions'] = CmsContentService::pick($raw, 'captions', ['below', 'overlay', 'hidden'], 'below');
                $section['items'] = [];
                foreach (array_values((array) ($raw['items'] ?? [])) as $item) {
                    if (! is_array($item)) continue;
                    $image = $cms->normalizeMediaUrl($item['image_url'] ?? '');
                    if (! $cms->safeMediaUrl($image)) continue;
                    $link = trim((string) ($item['link'] ?? ''));
                    $section['items'][] = [
                        'image_url' => $image,
                        'caption' => mb_substr(strip_tags((string) ($item['caption'] ?? '')), 0, 190),
                        'link' => $cms->safeUrl($link) ? $link : '',
                    ];
                    if (count($section['items']) >= 24) break;
                }
                if ($section['items'] === []) continue;
            } elseif ($type === 'video') {
                $video = $cms->toEmbedUrl($cms->normalizeMediaUrl($raw['video_url'] ?? ''));
                if (! $cms->isDirectVideo($video) && ! $cms->isValidVideoEmbed($video)) continue;
                $poster = $cms->normalizeMediaUrl($raw['poster_url'] ?? '');
                $section += [
                    'title' => $short('title'), 'text' => $text('text', 2000), 'video_url' => $video,
                    'poster_url' => $cms->safeMediaUrl($poster) ? $poster : '',
                    'autoplay' => ! empty($raw['autoplay']),
                    'width' => CmsContentService::pick($raw, 'width', ['narrow', 'normal', 'wide'], 'normal'),
                ];
            }
            if (! $this->isEmptySection($section)) {
                $section['style'] = $cms->parseStyle($raw['style'] ?? [], $type === 'section' ? (string) ($raw['background'] ?? '') : '');
                if ($type === 'section' && ! isset($raw['style']) && ! empty($raw['full_width'])) $section['style']['full_width'] = true;
                $section['hidden'] = ! empty($raw['hidden']);
                $section['label'] = mb_substr(strip_tags((string) ($raw['label'] ?? '')), 0, 80);
                $sections[] = $section;
                if (count($sections) >= 50) break;
            }
        }
        return $sections;
    }

    /** FAQ rows: category, question, Markdown answer. Empty rows are skipped. */
    private function parseFaqs(array $rows): array
    {
        $faqs = [];
        foreach (array_values($rows) as $row) {
            if (! is_array($row)) continue;
            $question = trim(mb_substr(strip_tags((string) ($row['question'] ?? '')), 0, 300));
            $answer = trim(mb_substr(strip_tags((string) ($row['answer'] ?? '')), 0, 5000));
            if ($question === '' && $answer === '') continue;
            if ($question === '' || $answer === '') {
                throw ValidationException::withMessages(['faqs' => 'Every FAQ needs both a question and an answer.']);
            }
            $faqs[] = ['category' => trim(mb_substr(strip_tags((string) ($row['category'] ?? '')), 0, 80)), 'question' => $question, 'answer' => $answer];
            if (count($faqs) >= 100) break;
        }
        return $faqs;
    }

    private function parseLinksText(string $text): array
    {
        $rows = [];
        foreach (preg_split('/\r?\n/', trim($text)) as $line) {
            if (trim($line) === '') continue;
            [$label, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            $rows[] = ['label' => $label, 'url' => $url];
        }
        return $this->parseLinkRows($rows, 'links_text');
    }

    /** Validate navigation links: label + safe relative, #anchor or http(s) URL; optional new tab and footer column. */
    private function parseLinkRows(array $rows, string $errorKey = 'links'): array
    {
        $links = [];
        foreach (array_values($rows) as $row) {
            if (! is_array($row)) continue;
            $label = trim(strip_tags((string) ($row['label'] ?? '')));
            $url = trim((string) ($row['url'] ?? ''));
            if ($label === '' && $url === '') continue;
            $safe = preg_match('#^/(?!/)[A-Za-z0-9/_.?=&%\#-]*$#', $url)
                || preg_match('/^#[A-Za-z0-9_-]+$/', $url)
                || (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true));
            if (! $safe || $label === '' || mb_strlen($label) > 100 || count($links) >= 30) {
                throw ValidationException::withMessages([$errorKey => 'Use at most 30 links, each with a label and a /path, #section or https:// address.']);
            }
            $link = ['label' => $label, 'url' => $url];
            if (! empty($row['new_tab'])) $link['new_tab'] = true;
            if (isset($row['column']) && $row['column'] !== '') $link['column'] = max(1, min(3, (int) $row['column']));
            $links[] = $link;
        }
        return $links;
    }

    private function parseHeader(array $raw, array $previous, bool $submitted): array
    {
        if (! $submitted) return $previous;
        $cms = app(CmsContentService::class);
        $cta = trim((string) ($raw['cta_url'] ?? ''));
        return [
            'logo_height' => max(24, min(96, (int) ($raw['logo_height'] ?? 48))),
            'show_name' => ! empty($raw['show_name']),
            'show_tagline' => ! empty($raw['show_tagline']),
            'sticky' => ! empty($raw['sticky']),
            'show_partners' => ! empty($raw['show_partners']),
            'show_auth' => ! empty($raw['show_auth']),
            'background' => $cms->hexColor($raw['background'] ?? ''),
            'text_color' => $cms->hexColor($raw['text_color'] ?? ''),
            'cta_label' => mb_substr(strip_tags((string) ($raw['cta_label'] ?? '')), 0, 60),
            'cta_url' => $cms->safeUrl($cta) ? $cta : '',
        ];
    }

    private function parseAnnouncement(array $raw): array
    {
        $cms = app(CmsContentService::class);
        $url = trim((string) ($raw['link_url'] ?? ''));
        return [
            'enabled' => ! empty($raw['enabled']),
            'text' => mb_substr(strip_tags((string) ($raw['text'] ?? '')), 0, 300),
            'link_label' => mb_substr(strip_tags((string) ($raw['link_label'] ?? '')), 0, 60),
            'link_url' => $cms->safeUrl($url) ? $url : '',
            'background' => $cms->hexColor($raw['background'] ?? ''),
            'text_color' => $cms->hexColor($raw['text_color'] ?? ''),
        ];
    }

    private function parseFooter(array $raw, array $previous, bool $submitted): array
    {
        if (! $submitted) return $previous;
        $cms = app(CmsContentService::class);
        $social = [];
        foreach (array_keys(CmsContentService::SOCIAL_NETWORKS) as $network) {
            $url = trim((string) data_get($raw, 'social.'.$network, ''));
            if ($url === '') continue;
            if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                throw ValidationException::withMessages(['footer.social.'.$network => 'Social links must be full https:// addresses.']);
            }
            $social[$network] = $url;
        }
        $email = trim((string) ($raw['email'] ?? ''));
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['footer.email' => 'Enter a valid contact email address.']);
        }
        $titles = [];
        foreach ([0, 1, 2] as $i) {
            $titles[$i] = mb_substr(strip_tags((string) data_get($raw, 'column_titles.'.$i, '')), 0, 60);
        }
        return [
            'show_logo' => ! empty($raw['show_logo']),
            'logo_height' => max(24, min(120, (int) ($raw['logo_height'] ?? 44))),
            'background' => $cms->hexColor($raw['background'] ?? ''),
            'text_color' => $cms->hexColor($raw['text_color'] ?? ''),
            'column_titles' => $titles,
            'email' => $email,
            'phone' => mb_substr(strip_tags((string) ($raw['phone'] ?? '')), 0, 60),
            'address' => mb_substr(strip_tags((string) ($raw['address'] ?? '')), 0, 300),
            'social' => $social,
            'copyright' => mb_substr(strip_tags((string) ($raw['copyright'] ?? '')), 0, 190),
        ];
    }

    private function parseColumns(array $input): array
    {
        $columns = [];
        foreach (array_slice(array_values($input), 0, 6) as $raw) {
            if (! is_array($raw)) continue;
            $width = (string) ($raw['width'] ?? '1/1');
            if (! in_array($width, CmsContentService::COLUMN_WIDTHS, true)) $width = '1/1';
            $widgets = $this->parseWidgets($raw['widgets'] ?? []);
            if ($widgets === []) continue;
            $columns[] = ['width' => $width, 'widgets' => $widgets];
        }
        return $columns;
    }

    private function parseWidgets(array $input): array
    {
        $widgets = [];
        foreach (array_values($input) as $raw) {
            if (! is_array($raw)) continue;
            $widget = $this->parseWidget($raw);
            if ($widget !== null) {
                $widgets[] = $widget;
                if (count($widgets) >= 20) break;
            }
        }
        return $widgets;
    }

    private function parseWidget(array $raw): ?array
    {
        $type = (string) ($raw['type'] ?? '');
        if (! array_key_exists($type, CmsContentService::WIDGETS)) return null;

        $cms = app(CmsContentService::class);
        $text = fn ($key, $max = 10000) => mb_substr(strip_tags((string) ($raw[$key] ?? '')), 0, $max);
        $short = fn ($key, $max = 190) => mb_substr(strip_tags((string) ($raw[$key] ?? '')), 0, $max);
        $url = function (string $key) use ($raw, $cms) {
            $value = trim((string) ($raw[$key] ?? ''));
            return $cms->safeUrl($value) ? $value : '';
        };

        $fields = [];
        switch ($type) {
            case 'heading':
                $level = (string) ($raw['level'] ?? 'h2');
                $fields['level'] = in_array($level, ['h2', 'h3', 'h4'], true) ? $level : 'h2';
                $fields['text'] = $short('text', 190);
                break;
            case 'text':
                $fields['content'] = $text('content', 10000);
                break;
            case 'image':
                $fields['image_url'] = $cms->safeMediaUrl($m = $cms->normalizeMediaUrl($raw['image_url'] ?? '')) ? $m : '';
                $fields['alt'] = $short('alt', 190);
                break;
            case 'button':
                $fields['label'] = $short('label', 100);
                $fields['url'] = $url('url');
                $style = (string) ($raw['style'] ?? 'primary');
                $fields['style'] = in_array($style, ['primary', 'outline', 'gold'], true) ? $style : 'primary';
                break;
            case 'spacer':
                $fields['height'] = max(0, min(200, (int) ($raw['height'] ?? 0)));
                break;
            case 'divider':
                return ['type' => 'divider'];
            case 'video':
                $embed = $cms->toEmbedUrl($cms->normalizeMediaUrl($raw['embed_url'] ?? ''));
                $fields['embed_url'] = ($cms->isValidVideoEmbed($embed) || $cms->isDirectVideo($embed)) ? $embed : '';
                break;
            case 'html':
                $rawHtml = mb_substr((string) ($raw['content'] ?? ''), 0, 20000);
                $fields['content'] = mb_substr($cms->sanitizeHtml($rawHtml), 0, 20000);
                break;
        }

        $required = ['heading' => 'text', 'text' => 'content', 'image' => 'image_url', 'button' => 'label', 'video' => 'embed_url', 'html' => 'content'];
        if (isset($required[$type]) && trim((string) ($fields[$required[$type]] ?? '')) === '') {
            return null;
        }

        return ['type' => $type] + $fields;
    }

    private function parseItems(array $input, array $fields, int $max): array
    {
        $items = [];
        foreach (array_values($input) as $raw) {
            if (! is_array($raw)) continue;
            $item = [];
            foreach ($fields as $key => $limit) {
                $item[$key] = mb_substr(strip_tags((string) ($raw[$key] ?? '')), 0, $limit);
            }
            if (array_filter(array_map('trim', $item))) $items[] = $item;
            if (count($items) >= $max) break;
        }
        return $items;
    }

    private function isEmptySection(array $section): bool
    {
        unset($section['type']);
        $flat = collect($section)->flatten()->map(fn ($v) => trim((string) $v))->filter()->all();
        return count($flat) === 0;
    }
}
