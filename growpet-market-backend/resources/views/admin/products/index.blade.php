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

    <section class="panel table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Type</th>
                    <th>Total stok</th>
                    <th>Terjual</th>
                    <th>Flags</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <div class="product-cell">
                                @if ($product->image_url)
                                    <img class="product-thumb" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                @else
                                    <span
                                        class="product-thumb product-thumb--empty">{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <strong>{{ $product->name }}</strong><br>
                                    <span class="muted">{{ $product->slug }}
                                        {{ $product->rarity ? '- ' . $product->rarity : '' }}</span>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge">{{ $product->type }}</span></td>
                        <td>
                            @if ($product->type === 'pet')
                                {{ number_format($product->total_variant_stock ?? 0, 0, ',', '.') }}
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                        <td>{{ number_format($product->sales_count, 0, ',', '.') }}</td>
                        <td>
                            @if ($product->featured)
                                <span class="badge">Featured</span>
                            @endif
                            @if ($product->best_seller)
                                <span class="badge">Best seller</span>
                            @endif
                        </td>
                        <td><span
                                class="badge {{ $product->active ? '' : 'muted' }}">{{ $product->active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="button small secondary" href="{{ route('admin.products.edit', $product) }}">Edit
                                    produk</a>
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <h3>Belum ada produk</h3>
                                <p>Tambahkan pet atau token pertama kamu, lalu lanjut atur harga detailnya.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="pagination">{{ $products->links() }}</div>
@endsection
