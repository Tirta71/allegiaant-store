<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mutation;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    public function index(Request $request): View
    {
        $variants = ProductVariant::query()
            ->with(['product', 'mutation'])
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->orderBy(Product::select('name')->whereColumn('products.id', 'product_variants.product_id'))
            ->orderBy('weight_kg')
            ->paginate(30)
            ->withQueryString();

        return view('admin.product-variants.index', [
            'variants' => $variants,
            'products' => Product::query()->pets()->orderBy('name')->get(),
            'mutations' => Mutation::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProductVariant::query()->create($this->validatedData($request));

        return back()->with('status', 'Varian pet berhasil dibuat.');
    }

    public function update(Request $request, ProductVariant $productVariant): RedirectResponse
    {
        $productVariant->update($this->validatedData($request, $productVariant));

        return back()->with('status', 'Varian pet berhasil diupdate.');
    }

    public function destroy(ProductVariant $productVariant): RedirectResponse
    {
        $productVariant->delete();

        return back()->with('status', 'Varian pet berhasil dihapus.');
    }

    private function validatedData(Request $request, ?ProductVariant $variant = null): array
    {
        return $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('type', Product::TYPE_PET)],
            'mutation_id' => ['required', Rule::exists('mutations', 'id')],
            'weight_kg' => ['required', 'numeric', 'min:0.01'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants')->ignore($variant)],
        ]) + ['active' => true];
    }
}
