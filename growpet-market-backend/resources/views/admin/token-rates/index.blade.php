@extends('admin.layouts.app')

@section('title', 'Rate Token')

@section('content')
    <section class="panel" style="margin-bottom: 16px;">
        <div class="section-head">
            <div>
                <h2>Tambah rate baru</h2>
                <p>Buat rate baru kalau harga token berubah. Nonaktifkan rate lama agar front-end memakai yang aktif.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.token-rates.store') }}" class="form-grid">
            @csrf
            <label>
                Produk token
                <select name="product_id" required>
                    @foreach ($tokenProducts as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Harga per 1K token
                <input type="number" min="1" name="price_per_thousand" value="15000" required>
                <span class="field-hint">Contoh: 15000 berarti Rp 15.000 untuk 1.000 token.</span>
            </label>
            <label>
                Min nominal
                <input type="number" min="1" name="min_nominal" value="5000" required>
            </label>
            <label>
                Stok token
                <input type="number" min="0" name="stock_token" value="50000" required>
                <span class="field-hint">Isi jumlah token yang tersedia, contoh 50000 untuk stok 50K token.</span>
            </label>
            <label>
                Berlaku mulai
                <input type="datetime-local" name="effective_from">
            </label>
            <label>
                Berlaku sampai
                <input type="datetime-local" name="effective_until">
            </label>
            <input type="hidden" name="active" value="0">
            <label class="check-row">
                <input type="checkbox" name="active" value="1" checked>
                Aktif dipakai front-end
            </label>
            <div class="full actions">
                <button class="button" type="submit">Simpan rate</button>
            </div>
        </form>
    </section>

    <section class="record-list">
        @forelse ($tokenRates as $rate)
            <details class="record-card">
                <summary>
                    <div>
                        <div class="record-title">
                            <strong>{{ $rate->product?->name }}</strong>
                            <span class="badge">1K = Rp {{ number_format($rate->price_per_thousand, 0, ',', '.') }}</span>
                            <span class="badge {{ $rate->active ? 'success' : 'muted' }}">{{ $rate->active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        <div class="record-meta">
                            <span>Min Rp {{ number_format($rate->min_nominal, 0, ',', '.') }}</span>
                            <span>Stok {{ number_format($rate->stock_token, 0, ',', '.') }} token</span>
                        </div>
                    </div>
                </summary>

                <div class="record-card__body">
                    <form method="POST" action="{{ route('admin.token-rates.update', $rate) }}" class="form-grid">
                        @csrf
                        @method('PUT')
                        <label>
                            Produk token
                            <select name="product_id" required>
                                @foreach ($tokenProducts as $product)
                                    <option value="{{ $product->id }}" @selected($rate->product_id === $product->id)>{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            Harga per 1K token
                            <input type="number" min="1" name="price_per_thousand" value="{{ $rate->price_per_thousand }}" required>
                        </label>
                        <label>
                            Min nominal
                            <input type="number" min="1" name="min_nominal" value="{{ $rate->min_nominal }}" required>
                        </label>
                        <label>
                            Stok token
                            <input type="number" min="0" name="stock_token" value="{{ $rate->stock_token }}" required>
                        </label>
                        <label>
                            Berlaku mulai
                            <input type="datetime-local" name="effective_from" value="{{ $rate->effective_from?->format('Y-m-d\TH:i') }}">
                        </label>
                        <label>
                            Berlaku sampai
                            <input type="datetime-local" name="effective_until" value="{{ $rate->effective_until?->format('Y-m-d\TH:i') }}">
                        </label>
                        <input type="hidden" name="active" value="0">
                        <label class="check-row">
                            <input type="checkbox" name="active" value="1" @checked($rate->active)>
                            Aktif dipakai front-end
                        </label>
                        <div class="full actions">
                            <button class="button small" type="submit">Simpan perubahan</button>
                            <button class="button small danger" type="submit" form="delete-token-rate-{{ $rate->id }}">Nonaktifkan</button>
                        </div>
                    </form>
                    <form id="delete-token-rate-{{ $rate->id }}" method="POST" action="{{ route('admin.token-rates.destroy', $rate) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </details>
        @empty
            <div class="empty-state">
                <h3>Belum ada rate token</h3>
                <p>Buat rate token supaya produk token bisa dihitung oleh front-end.</p>
            </div>
        @endforelse
    </section>
@endsection
