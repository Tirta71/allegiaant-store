@extends('admin.layouts.app')

@section('title', 'Produk')

@section('content')


    <div class="toolbar">
        <a href="{{ route('admin.products.create') }}" class="button">Tambah produk baru</a>
        <a href="{{ route('admin.product-variants.index') }}" class="button secondary">Atur harga pet</a>
    </div>

    <form class="toolbar" method="GET">
        <div class="filters">
            <label>
                Cari produk
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="Contoh: Unicorn, token, Divine">
            </label>
            <label>
                Jenis produk
                <select name="type">
                    <option value="">Semua</option>
                    <option value="pet" @selected(request('type') === 'pet')>Pet</option>
                    <option value="token" @selected(request('type') === 'token')>Token</option>
                </select>
            </label>
            <button class="button secondary" type="submit">Terapkan filter</button>
        </div>
    </form>

    <section class="product-grid" aria-label="Daftar produk">
        @forelse ($products as $product)
            <article class="product-card {{ $product->active ? '' : 'is-muted' }}">
                <div class="product-card__media">
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                    @else
                        <span>{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                    @endif
                </div>

                <div class="product-card__content">
                    <div class="product-card__head">
                        <div>
                            <h2>{{ $product->name }}</h2>
                            <p>{{ $product->slug }}</p>
                        </div>
                        <span
                            class="status-pill {{ $product->active ? 'success' : 'muted' }}">{{ $product->active ? 'Active' : 'Inactive' }}</span>
                    </div>

                    <div class="product-card__badges">
                        <span class="badge">{{ ucfirst($product->type) }}</span>
                        @if ($product->rarity)
                            <span class="badge muted">{{ $product->rarity }}</span>
                        @endif
                        @if ($product->featured)
                            <span class="badge warning">Featured</span>
                        @endif
                        @if ($product->best_seller)
                            <span class="badge success">Best seller</span>
                        @endif
                    </div>

                    <div class="product-card__stats">
                        <div>
                            <span>Total stok</span>
                            <strong>
                                @if ($product->type === 'pet')
                                    {{ number_format($product->total_variant_stock ?? 0, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </strong>
                        </div>
                        <div>
                            <span>Terjual</span>
                            <strong>{{ number_format($product->sales_count, 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <span>Urutan</span>
                            <strong>{{ number_format($product->sort_order, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="product-card__actions">
                        <a class="button small secondary" href="{{ route('admin.products.edit', $product) }}">Edit produk</a>
                        @if ($product->type === 'pet')
                            <a class="button small soft"
                                href="{{ route('admin.product-variants.index', ['product_id' => $product->id]) }}">Harga</a>
                        @else
                            <a class="button small soft" href="{{ route('admin.token-rates.index') }}">Rate</a>
                        @endif
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}">
                            @csrf
                            @method('DELETE')
                            <button class="button small danger" type="submit">Hapus</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="panel empty-state product-empty">
                <h3>Belum ada produk</h3>
                <p>Tambahkan pet atau token pertama kamu, lalu lanjut atur harga detailnya.</p>
            </div>
        @endforelse
    </section>

    <div class="pagination">{{ $products->links() }}</div>
@endsection
