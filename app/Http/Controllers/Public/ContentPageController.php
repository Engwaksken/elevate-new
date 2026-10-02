<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CmsContentService;

class ContentPageController extends Controller
{
    public function home(CmsContentService $cms)
    {
        $content = $cms->published('home');
        return $content ? view('cms.page', compact('content')) : view('home');
    }

    public function privacy(CmsContentService $cms)
    {
        $content = $cms->published('privacy-policy');
        return $content ? view('cms.page', compact('content')) : view('legal.privacy');
    }

    public function terms(CmsContentService $cms)
    {
        $content = $cms->published('terms');
        return $content ? view('cms.page', compact('content')) : view('legal.terms');
    }

    public function faqs(CmsContentService $cms)
    {
        $content = $cms->published('faqs') ?? ['title' => 'Frequently Asked Questions', 'body' => "## How do I access my courses?\nSign in and open My Learning.\n\n## Where can I get help?\nUse Help & Support in your participant dashboard or app."];
        return view('cms.page', compact('content'));
    }

    public function show(string $slug, CmsContentService $cms)
    {
        abort_if(array_key_exists($slug, CmsContentService::PAGES), 404);
        $content = $cms->published($slug);
        abort_unless($content, 404);
        return view('cms.page', compact('content'));
    }
}
