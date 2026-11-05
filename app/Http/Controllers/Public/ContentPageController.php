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
        return view('legal.document', ['doc' => $cms->legalDocument('privacy-policy')]);
    }

    public function terms(CmsContentService $cms)
    {
        return view('legal.document', ['doc' => $cms->legalDocument('terms')]);
    }

    public function faqs(CmsContentService $cms)
    {
        $content = $cms->published('faqs');
        // Published FAQ items, else the built-in starter list (unless the page was published with text only).
        $faqs = data_get($content, 'settings.faqs');
        if (! is_array($faqs) || $faqs === []) {
            $faqs = filled($content['body'] ?? null) ? [] : CmsContentService::defaultFaqs();
        }
        return view('cms.faqs', ['content' => $content ?? ['title' => 'Frequently Asked Questions'], 'faqs' => $faqs]);
    }

    public function show(string $slug, CmsContentService $cms)
    {
        abort_if(array_key_exists($slug, CmsContentService::PAGES), 404);
        $content = $cms->published($slug);
        abort_unless($content, 404);
        return view('cms.page', compact('content'));
    }
}
