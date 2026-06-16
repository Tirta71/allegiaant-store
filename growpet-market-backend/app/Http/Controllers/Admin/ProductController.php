<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->withSum('activeVariants as total_variant_stock', 'stock')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', $search)
                        ->orWhere('slug', 'like', $search)
                        ->orWhere('rarity', 'like', $search);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['type' => Product::TYPE_PET, 'active' => true]),
            'method' => 'POST',
            'action' => route('admin.products.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::query()->create($this->validatedData($request));

        return redirect()->route('admin.products.index')->with('status', 'Produk berhasil dibuat.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'method' => 'PUT',
            'action' => route('admin.products.update', $product),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validatedData($request, $product));

        return redirect()->route('admin.products.index')->with('status', 'Produk berhasil diupdate.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Produk berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', Rule::unique('products')->ignore($product)],
            'type' => ['required', Rule::in([Product::TYPE_PET, Product::TYPE_TOKEN])],
            'name' => ['required', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'rarity' => ['nullable', 'string', 'max:255'],
            'featured' => ['nullable', 'boolean'],
            'best_seller' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]) + [
            'featured' => false,
            'best_seller' => false,
            'active' => false,
        ];

        $data['image_url'] = $this->cleanImageUrl($data['image_url'] ?? null);

        return $data;
    }

    private function cleanImageUrl(?string $imageUrl): ?string
    {
        $imageUrl = trim((string) $imageUrl);

        if ($imageUrl === '') {
            return null;
        }

        return preg_replace('#/revision/.*$#', '', $imageUrl);
    }
}
