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
        return view('admin.cms.edit', compact('page'));
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
        ]);
        $sections = $this->parseSections($request->input('sections', []));
        $links = [];
        foreach (preg_split('/\r?\n/', trim($data['links_text'] ?? '')) as $line) {
            if (trim($line) === '') continue;
            [$label, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            $safe = preg_match('#^/(?!/)[A-Za-z0-9/_.?=&%\#-]*$#', $url) || (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true));
            if (! $safe || $label === '' || strlen($label) > 100 || count($links) >= 30) {
                throw ValidationException::withMessages(['links_text' => 'Use at most 30 links, one per line: Label | /path or https://example.com.']);
            }
            $links[] = ['label' => $label, 'url' => $url];
        }
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
        if ($data['action'] === 'publish' && ! in_array($page->slug, ['site-header','site-footer','learning','jobs','library','events','mentor-signup','employer-signup','register','login'], true) && blank($data['body'] ?? null) && empty($sections)) {
            throw ValidationException::withMessages(['body' => 'Add page content before publishing.']);
        }
        $attributes = collect($data)->only(['title', 'summary', 'body'])->all();
        $attributes['settings'] = ['links' => $links, 'field_labels' => $labels, 'sections' => $sections, 'button_label' => $data['button_label'] ?? null, 'signup_open' => $request->boolean('signup_open')];
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
        return view('cms.page', ['content' => $page->draft(), 'preview' => true]);
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
            $section = ['type' => $type];
            if ($type === 'hero') {
                $section += [
                    'eyebrow' => $short('eyebrow', 100), 'title' => $short('title'), 'text' => $text('text', 2000),
                    'primary_label' => $short('primary_label', 100), 'primary_url' => $url('primary_url'),
                    'secondary_label' => $short('secondary_label', 100), 'secondary_url' => $url('secondary_url'),
                    'image_url' => $url('image_url'),
                ];
            } elseif ($type === 'text') {
                $section += ['title' => $short('title'), 'content' => $text('content')];
            } elseif ($type === 'image_text') {
                $section += ['title' => $short('title'), 'content' => $text('content'), 'image_url' => $url('image_url'), 'reverse' => ! empty($raw['reverse'])];
            } elseif ($type === 'cta') {
                $section += ['title' => $short('title'), 'content' => $text('content', 2000), 'button_label' => $short('button_label', 100), 'button_url' => $url('button_url')];
            } elseif ($type === 'features') {
                $section['title'] = $short('title');
                $section['intro'] = $text('intro', 2000);
                $section['items'] = $this->parseItems($raw['items'] ?? [], ['icon' => 80, 'title' => 190, 'text' => 1000], 12);
            } elseif ($type === 'stats') {
                $section['title'] = $short('title');
                $section['items'] = $this->parseItems($raw['items'] ?? [], ['value' => 80, 'label' => 190], 8);
            } elseif ($type === 'section') {
                $columns = $this->parseColumns($raw['columns'] ?? []);
                if ($columns === []) continue;
                $section['background'] = $this->parseBackground($raw['background'] ?? '');
                $section['full_width'] = ! empty($raw['full_width']);
                $section['columns'] = $columns;
            }
            if (! $this->isEmptySection($section)) {
                $sections[] = $section;
                if (count($sections) >= 50) break;
            }
        }
        return $sections;
    }

    private function parseBackground($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === 'soft') return $value;
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) return strtolower($value);
        return '';
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
                $fields['image_url'] = $url('image_url');
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
                $embed = trim((string) ($raw['embed_url'] ?? ''));
                $fields['embed_url'] = $cms->isValidVideoEmbed($embed) ? $embed : '';
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
