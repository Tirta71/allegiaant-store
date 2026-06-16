@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="quick-grid">
        <a class="quick-card" href="{{ route('admin.products.index') }}">
            <span>Produk</span>
            <h3>Pet dan token</h3>
            <p>Kelola nama, deskripsi, rarity, warna, stok ringkasan, dan status tampil.</p>
        </a>
        <a class="quick-card" href="{{ route('admin.product-variants.index') }}">
            <span>Harga Pet</span>
            <h3>Mutasi + berat</h3>
            <p>Set harga final untuk setiap kombinasi pet, mutasi, dan berat kg.</p>
        </a>
        <a class="quick-card" href="{{ route('admin.token-rates.index') }}">
            <span>Token</span>
            <h3>Rate nominal bebas</h3>
            <p>Atur harga per 1K token yang dipakai kalkulator token di front-end.</p>
        </a>
        <a class="quick-card" href="{{ route('admin.orders.index') }}">
            <span>Order</span>
            <h3>Proses transaksi</h3>
            <p>Lihat buyer, item, payment, dan update status delivery.</p>
        </a>
    </section>

    <section class="grid stats">
        @foreach ($stats as $label => $value)
            <article class="card stat">
                <span>{{ str($label)->headline() }}</span>
                <strong>{{ number_format($value, 0, ',', '.') }}</strong>
            </article>
        @endforeach
    </section>

    <section class="panel" style="margin-top: 20px;">
        <div class="section-head">
            <div>
                <h2>Order terbaru</h2>
                <p>Transaksi terakhir dari API checkout.</p>
            </div>
            <a class="button secondary" href="{{ route('admin.orders.index') }}">Lihat semua</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Buyer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestOrders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->code }}</a></td>
                            <td>{{ $order->buyer_roblox_username }}</td>
                            <td>Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                            <td><span class="badge">{{ str($order->status)->headline() }}</span></td>
                            <td>{{ $order->created_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <h3>Belum ada order masuk</h3>
                                    <p>Begitu front-end membuat checkout lewat API, order terbaru akan muncul di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
