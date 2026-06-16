@extends('admin.layouts.app')

@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')

@section('content')
    <div class="toolbar">
        <a class="button secondary" href="{{ route('admin.products.index') }}">Kembali</a>
    </div>

    <form method="POST" action="{{ $action }}" class="panel grid">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <section class="form-section">
            <h2>Identitas produk</h2>
            <div class="form-grid">
                <label>
                    Nama produk
                    <input name="name" value="{{ old('name', $product->name) }}" placeholder="Unicorn" required>
                    <span class="field-hint">Nama yang tampil di kartu katalog.</span>
                </label>
                <label>
                    Image URL
                    <input name="image_url" value="{{ old('image_url', $product->image_url) }}"
                        placeholder="https://example.com/unicorn.png">
                    <span class="field-hint">Opsional. Bisa pakai URL gambar atau path seperti
                        /storage/products/unicorn.png.</span>
                </label>
                <label>
                    Slug API
                    <input name="slug" value="{{ old('slug', $product->slug) }}" placeholder="unicorn" required>
                    <span class="field-hint">Contoh bagus: <strong>queen-bee</strong>, <strong>garden-token</strong>.</span>
                </label>
                <label>
                    Jenis produk
                    <select name="type" required>
                        <option value="pet" @selected(old('type', $product->type) === 'pet')>Pet</option>
                        <option value="token" @selected(old('type', $product->type) === 'token')>Token</option>
                    </select>
                    <span class="field-hint">Pet punya varian harga. Token punya rate per 1K.</span>
                </label>
                <label>
                    Rarity
                    <input name="rarity" value="{{ old('rarity', $product->rarity) }}"
                        placeholder="Legendary, Mythical, Divine">
                    <span class="field-hint">Kosongkan untuk produk token.</span>
                </label>
                <label>
                    Sort order
                    <input type="number" name="sort_order" min="0"
                        value="{{ old('sort_order', $product->sort_order ?? 0) }}" required>
                    <span class="field-hint">Angka kecil tampil lebih dulu jika nanti dipakai sorting manual.</span>
                </label>
            </div>
        </section>

        <section class="form-section">
            <h2>Status katalog</h2>
            <div class="form-grid">
                @if ($product->exists)
                    <div class="notice">
                        Terjual otomatis: <strong>{{ number_format($product->sales_count, 0, ',', '.') }}</strong>
                    </div>
                @endif
                <div class="grid">
                    <input type="hidden" name="featured" value="0">
                    <label class="check-row">
                        <input type="checkbox" name="featured" value="1" @checked(old('featured', $product->featured))>
                        Tampilkan sebagai featured
                    </label>
                    <input type="hidden" name="best_seller" value="0">
                    <label class="check-row">
                        <input type="checkbox" name="best_seller" value="1" @checked(old('best_seller', $product->best_seller))>
                        Tandai best seller
                    </label>
                    <input type="hidden" name="active" value="0">
                    <label class="check-row">
                        <input type="checkbox" name="active" value="1" @checked(old('active', $product->active ?? true))>
                        Aktif dan tampil di front-end
                    </label>
                </div>
            </div>
        </section>

        <div class="full actions">
            <button class="button" type="submit">Simpan</button>
            <a class="button secondary" href="{{ route('admin.products.index') }}">Batal</a>
            @if ($product->exists && $product->type === 'pet')
                <a class="button soft"
                    href="{{ route('admin.product-variants.index', ['product_id' => $product->id]) }}">Lanjut atur harga
                    pet</a>
            @elseif ($product->exists && $product->type === 'token')
                <a class="button soft" href="{{ route('admin.token-rates.index') }}">Lanjut atur rate token</a>
            @endif
        </div>
    </form>
@endsection
