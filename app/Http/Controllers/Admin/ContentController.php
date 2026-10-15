<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    public function index()
    {
        $contents = Content::latest()->paginate(10);

        return view('admin.contents.index', compact('contents'));
    }

    public function create()
    {
        return view('admin.contents.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('contents', 'public');
        }

        $slug = Str::slug($validated['title']);
        $baseSlug = $slug;
        $counter = 1;

        while (Content::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        Content::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'body' => $validated['body'] ?? null,
            'image_path' => $imagePath,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('admin.contents.index')
            ->with('success', 'Konten berhasil ditambahkan.');
    }

    public function edit(Content $content)
    {
        return view('admin.contents.edit', compact('content'));
    }

    public function update(Request $request, Content $content)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            if ($content->image_path) {
                Storage::disk('public')
                    ->delete($content->image_path);
            }

            $content->image_path = $request->file('image')
                ->store('contents', 'public');
        }

        $slug = Str::slug($validated['title']);
        $baseSlug = $slug;
        $counter = 1;

        while (
            Content::where('slug', $slug)
                ->where('id', '!=', $content->id)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $content->title = $validated['title'];
        $content->slug = $slug;
        $content->body = $validated['body'] ?? null;
        $content->is_published = $request->boolean('is_published');
        $content->save();

        return redirect()
            ->route('admin.contents.index')
            ->with('success', 'Konten berhasil diperbarui.');
    }

    public function destroy(Content $content)
    {
        if ($content->image_path) {
            Storage::disk('public')
                ->delete($content->image_path);
        }

        $content->delete();

        return redirect()
            ->route('admin.contents.index')
            ->with('success', 'Konten berhasil dihapus.');
    }
}
