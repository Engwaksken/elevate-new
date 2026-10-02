<?php

namespace App\Services;

use App\Models\CmsPage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CmsContentService
{
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
}
