<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Services\CareerDocumentEditorService;
use Illuminate\Http\Request;

class ResumeExtrasController extends Controller
{
    public function store(Request $request, Resume $resume, string $section)
    {
        $data = $this->validated($request, $resume, $section);
        $resume->{$section}()->create($data + ['position' => $resume->{$section}()->count()]);
        return back()->with('success', 'Resume section added.');
    }

    public function update(Request $request, Resume $resume, string $section, int $id)
    {
        $data = $this->validated($request, $resume, $section);
        $resume->{$section}()->findOrFail($id)->update($data);
        return back()->with('success', 'Resume section updated.');
    }

    public function destroy(Request $request, Resume $resume, string $section, int $id)
    {
        $this->own($request, $resume, $section);
        $resume->{$section}()->findOrFail($id)->delete();
        return back()->with('success', 'Resume section removed.');
    }

    private function validated(Request $request, Resume $resume, string $section): array
    {
        $this->own($request, $resume, $section);
        return app(CareerDocumentEditorService::class)->validate(['title' => $resume->title, 'template' => $resume->template, $section => [$request->all()]], true, $request->user()->id)[$section][0];
    }

    private function own(Request $request, Resume $resume, string $section): void
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        abort_unless(in_array($section, ['projects', 'referees'], true), 404);
    }
}
