<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ProductController (Admin)
 * ------------------------------------------------------------------
 * CRUD katalog produk. Produk disimpan pada tabel `products` (database
 * Laravel) dan menjadi SATU SUMBER data untuk homepage & panel admin,
 * sehingga perubahan di sini langsung tercermin pada katalog pembeli.
 */
class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products', [
            'products' => Product::ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products-form', [
            'product' => new Product,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Product::create($data);

        return redirect()
            ->route('admin.products')
            ->with('status', 'Produk "'.$data['name'].'" berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products-form', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $product->update($data);

        return redirect()
            ->route('admin.products')
            ->with('status', 'Produk "'.$product->name.'" berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->delete();

        return redirect()
            ->route('admin.products')
            ->with('status', 'Produk "'.$name.'" berhasil dihapus.');
    }

    /**
     * Validasi + normalisasi input produk.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['stock'] = (int) ($data['stock'] ?? 0);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['category'] = $data['category'] ?? 'Aci';
        $data['description'] = $data['description'] ?? '';
        $data['slug'] = $this->uniqueSlug($data['title'] ?: $data['name'], $product);

        if ($request->hasFile('image')) {
            $data['image'] = 'storage/'.$request->file('image')->store('products', 'public');
        } else {
            unset($data['image']); // pertahankan gambar lama saat edit
        }

        return $data;
    }

    private function uniqueSlug(string $source, ?Product $product = null): string
    {
        $base = Str::slug($source) ?: 'produk';
        $slug = $base;
        $suffix = 1;

        while (
            Product::where('slug', $slug)
                ->when($product?->exists, fn ($query) => $query->whereKeyNot($product->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
