<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TokenRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TokenRateController extends Controller
{
    public function index(): View
    {
        return view('admin.token-rates.index', [
            'tokenRates' => TokenRate::query()->with('product')->latest()->get(),
            'tokenProducts' => Product::query()->tokens()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TokenRate::query()->create($this->validatedData($request));

        return back()->with('status', 'Rate token berhasil dibuat.');
    }

    public function update(Request $request, TokenRate $tokenRate): RedirectResponse
    {
        $tokenRate->update($this->validatedData($request));

        return back()->with('status', 'Rate token berhasil diupdate.');
    }

    public function destroy(TokenRate $tokenRate): RedirectResponse
    {
        $tokenRate->update(['active' => false]);

        return back()->with('status', 'Rate token dinonaktifkan.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('type', Product::TYPE_TOKEN)],
            'price_per_thousand' => ['required', 'integer', 'min:1'],
            'min_nominal' => ['required', 'integer', 'min:1'],
            'stock_token' => ['required', 'integer', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'active' => ['nullable', 'boolean'],
        ]) + ['active' => false];
    }
}
