@extends('admin.layouts.app')

@section('title', 'Harga Pet')

@section('content')
    <section class="panel" style="margin-bottom: 16px;">
        <div class="section-head">
            <div>
                <h2>Tambah varian baru</h2>
                <p>Pakai ini kalau ada kombinasi mutasi dan berat baru yang belum ada.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.product-variants.store') }}" class="form-grid">
            @csrf
            <label>
                Pet
                <select name="product_id" required>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Mutasi
                <select name="mutation_id" required>
                    @foreach ($mutations as $mutation)
                        <option value="{{ $mutation->id }}">{{ $mutation->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Berat kg
                <input type="number" step="0.01" min="0.01" name="weight_kg" placeholder="2.10" required>
                <span class="field-hint">Gunakan angka desimal, contoh 1.40 atau 5.20.</span>
            </label>
            <label>
                Harga final
                <input type="number" min="0" name="price" placeholder="160000" required>
                <span class="field-hint">Harga ini yang dipakai cart dan checkout.</span>
            </label>
            <label>
                Stok varian
                <input type="number" min="0" name="stock" value="0" required>
            </label>
            <label>
                SKU
                <input name="sku" placeholder="Opsional">
            </label>
            <div class="full actions">
                <button class="button" type="submit">Simpan varian</button>
            </div>
        </form>
    </section>

    <form class="toolbar" method="GET">
        <div class="filters">
            <label>
                Tampilkan varian untuk pet
                <select name="product_id">
                    <option value="">Semua pet</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <button class="button secondary" type="submit">Terapkan filter</button>
        </div>
    </form>

    <section class="record-list">
        @forelse ($variants as $variant)
            <details class="record-card">
                <summary>
                    <div>
                        <div class="record-title">
                            <strong>{{ $variant->product?->name }}</strong>
                            <span class="badge">{{ $variant->mutation?->name }}</span>
                            <span class="badge muted">{{ number_format((float) $variant->weight_kg, 2, ',', '.') }} kg</span>
                        </div>
                        <div class="record-meta">
                            <span>Harga Rp {{ number_format($variant->price, 0, ',', '.') }}</span>
                            <span>Stok {{ number_format($variant->stock, 0, ',', '.') }}</span>
                            <span>Terjual {{ number_format($variant->sales_count, 0, ',', '.') }}</span>
                            @if ($variant->sku)
                                <span>SKU {{ $variant->sku }}</span>
                            @endif
                        </div>
                    </div>
                </summary>

                <div class="record-card__body">
                    <form method="POST" action="{{ route('admin.product-variants.update', $variant) }}" class="form-grid">
                        @csrf
                        @method('PUT')
                        <label>
                            Pet
                            <select name="product_id" required>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected($variant->product_id === $product->id)>{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            Mutasi
                            <select name="mutation_id" required>
                                @foreach ($mutations as $mutation)
                                    <option value="{{ $mutation->id }}" @selected($variant->mutation_id === $mutation->id)>{{ $mutation->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            Berat kg
                            <input type="number" step="0.01" min="0.01" name="weight_kg" value="{{ $variant->weight_kg }}" required>
                        </label>
                        <label>
                            Harga final
                            <input type="number" min="0" name="price" value="{{ $variant->price }}" required>
                        </label>
                        <label>
                            Stok varian
                            <input type="number" min="0" name="stock" value="{{ $variant->stock }}" required>
                        </label>
                        <label>
                            SKU
                            <input name="sku" value="{{ $variant->sku }}">
                        </label>
                        <div class="full actions">
                            <button class="button small" type="submit">Simpan perubahan</button>
                            <button class="button small danger" type="submit" form="delete-variant-{{ $variant->id }}">Hapus</button>
                        </div>
                    </form>
                    <form id="delete-variant-{{ $variant->id }}" method="POST" action="{{ route('admin.product-variants.destroy', $variant) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </details>
        @empty
            <div class="empty-state">
                <h3>Belum ada varian pet</h3>
                <p>Tambahkan varian pertama supaya pet punya pilihan mutasi, berat, harga, dan stok.</p>
            </div>
        @endforelse
    </section>

    <div class="pagination">{{ $variants->links() }}</div>
@endsection
