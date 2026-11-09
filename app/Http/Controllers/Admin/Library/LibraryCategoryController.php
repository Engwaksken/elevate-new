<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Library categories, managed from the Categories window on admin/library
 * and the "+ New category" option in the book forms (JSON).
 */
class LibraryCategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $category = LibraryCategory::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['id' => $category->id, 'name' => $category->name], 201);
        }

        return back()->with('success', "Category \"{$category->name}\" added.")->with('open_library_modal', 'categories');
    }

    public function update(Request $request, LibraryCategory $category)
    {
        $data = $this->validated($request, $category);

        $category->update([
            'name' => $data['name'],
            'slug' => $category->name === $data['name'] ? $category->slug : $this->uniqueSlug($data['name'], $category->id),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', "Category \"{$category->name}\" updated.")->with('open_library_modal', 'categories');
    }

    public function destroy(LibraryCategory $category)
    {
        // Books (including archived ones) keep their category; switch it off instead.
        $inUse = $category->resources()->withTrashed()->count();

        if ($inUse > 0) {
            return back()
                ->withErrors(['category' => "\"{$category->name}\" is used by {$inUse} ".Str::plural('book', $inUse).'. Turn it off instead, or move those books to another category first.'])
                ->with('open_library_modal', 'categories');
        }

        $category->delete();

        return back()->with('success', "Category \"{$category->name}\" deleted.")->with('open_library_modal', 'categories');
    }

    private function validated(Request $request, ?LibraryCategory $category = null): array
    {
        $request->merge(['name' => trim(preg_replace('/\s+/', ' ', (string) $request->input('name')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120', function ($attribute, $value, $fail) use ($category) {
                // Case-insensitive on every database ("Health" and "health" are the same category).
                $taken = LibraryCategory::whereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)])
                    ->when($category, fn ($q) => $q->whereKeyNot($category->id))
                    ->exists();
                if ($taken) {
                    $fail('A library category with this name already exists.');
                }
            }],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;

        while (LibraryCategory::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
